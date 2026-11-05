<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\DataTransfer\Contracts\JobInstances;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Services\JobTypeGate;
use Webkul\MasterDataManagement\Listeners\AddImportReviewCounts;
use Webkul\MasterDataManagement\Services\ScheduledImport;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

#[Description('Import the next file waiting in the inbox folder of a master data import job')]
#[Signature('masterdata:import
        {job_code : The code of the import job instance}
        {--file= : A path on the inbox disk; the oldest file in the job inbox folder when left out}
        {--user= : The e-mail of the admin the import runs as; masterdata.scheduled_imports.user when left out}
        {--sync : Import in this process instead of queueing the job}')]
class ImportCommand extends Command
{
    /**
     * Queue, or run, the import job on the next waiting file.
     */
    public function handle(JobInstancesRepository $jobInstancesRepository, AdminRepository $adminRepository, JobTypeGate $jobTypeGate, ScheduledImport $scheduledImport): int
    {
        $code = (string) $this->argument('job_code');

        $jobInstance = $jobInstancesRepository->findOneByField('code', $code);

        if (! $jobInstance instanceof JobInstances || $jobInstance->type !== 'import') {
            return $this->refuse(trans('masterdata::app.imports.schedule.job-not-found', ['code' => $code]));
        }

        if (! AddImportReviewCounts::isMasterDataImport($jobInstance->entity_type)) {
            return $this->refuse(trans('masterdata::app.imports.schedule.not-masterdata', ['code' => $code]));
        }

        if ($jobTypeGate->hides($jobInstance->entity_type)) {
            return $this->refuse(trans('data_transfer::app.job.module-disabled', ['type' => $jobInstance->entity_type]));
        }

        $email = (string) ($this->option('user') ?: config('masterdata.scheduled_imports.user'));

        if ($email === '') {
            return $this->refuse(trans('masterdata::app.imports.schedule.no-user'));
        }

        $author = $adminRepository->findOneByField('email', $email);

        if (! $author instanceof Admin || ! $author->status) {
            return $this->refuse(trans('masterdata::app.imports.schedule.user-not-found', ['email' => $email]));
        }

        $path = $this->option('file');

        if (is_string($path) && $path !== '' && ! $scheduledImport->disk()->exists($path)) {
            return $this->refuse(trans('masterdata::app.imports.schedule.file-not-found', ['path' => $path, 'disk' => $scheduledImport->diskName()]));
        }

        $path = is_string($path) && $path !== '' ? $path : $scheduledImport->nextFile($code);

        $track = $path === null ? null : $scheduledImport->run($jobInstance, $path, $author, (bool) $this->option('sync'));

        if (! $track instanceof JobTrack) {
            $this->components->info(trans('masterdata::app.imports.schedule.nothing', ['path' => $scheduledImport->inbox($code), 'disk' => $scheduledImport->diskName()]));

            return self::SUCCESS;
        }

        $this->components->info(trans($this->option('sync') ? 'masterdata::app.imports.schedule.imported' : 'masterdata::app.imports.schedule.queued', [
            'file' => basename((string) $path),
            'code' => $code,
            'id'   => $track->id,
        ]));

        return self::SUCCESS;
    }

    /**
     * Report the error and end with a failure code.
     */
    protected function refuse(string $message): int
    {
        $this->components->error($message);

        return self::FAILURE;
    }
}
