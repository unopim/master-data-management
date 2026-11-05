<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Contracts\JobInstances;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\User\Models\Admin;

/**
 * Runs a master data import job instance on a file an integration tool dropped on a filesystem disk, through the
 * same job track and ImportTrackBatch pipeline as "Import now" in the admin. A picked file is first moved to
 * `processed/`, which claims it, so a file is imported once even when two runs overlap.
 */
class ScheduledImport
{
    /**
     * The file extensions the import readers accept.
     */
    public const array EXTENSIONS = ['csv', 'xls', 'xlsx'];

    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
    ) {}

    /**
     * The disk the inbox folders live on.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    /**
     * The name of the disk the inbox folders live on.
     */
    public function diskName(): string
    {
        return (string) config('masterdata.scheduled_imports.disk', 'local');
    }

    /**
     * The inbox folder of a job.
     */
    public function inbox(string $jobCode): string
    {
        return trim((string) config('masterdata.scheduled_imports.path', 'masterdata/imports'), '/').'/'.$jobCode;
    }

    /**
     * The oldest importable file waiting in the job's inbox folder, or null when it is empty.
     */
    public function nextFile(string $jobCode): ?string
    {
        $disk = $this->disk();

        $files = array_values(array_filter(
            $disk->files($this->inbox($jobCode)),
            fn (string $path): bool => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true),
        ));

        usort($files, fn (string $a, string $b): int => [$disk->lastModified($a), $a] <=> [$disk->lastModified($b), $b]);

        return $files[0] ?? null;
    }

    /**
     * Claim the file, copy it to the private disk the import reads from, and start the job on it as the admin.
     *
     * Returns null when another run claimed the file first.
     */
    public function run(JobInstances $jobInstance, string $path, Admin $author, bool $sync = false): ?JobTrack
    {
        $disk = $this->disk();

        $claimed = trim(dirname($path), './').'/processed/'.now()->format('Ymd-His').'-'.basename($path);

        if (! $disk->exists($path) || ! $disk->move($path, $claimed)) {
            return null;
        }

        $filePath = 'imports/'.time().'-'.basename($path);

        Storage::disk('private')->writeStream($filePath, $disk->readStream($claimed));

        $jobInstance = $this->jobInstancesRepository->update(['file_path' => $filePath], $jobInstance->id);

        $track = $this->jobTrackRepository->create([
            'type'                  => 'import',
            'state'                 => Import::STATE_PENDING,
            'validation_strategy'   => $jobInstance->validation_strategy,
            'allowed_errors'        => $jobInstance->allowed_errors,
            'field_separator'       => $jobInstance->field_separator,
            'file_path'             => $filePath,
            'images_directory_path' => $jobInstance->images_directory_path,
            'meta'                  => $jobInstance->toArray(),
            'job_instances_id'      => $jobInstance->id,
            'user_id'               => $author->id,
            'action'                => $jobInstance->action,
        ]);

        $sync ? ImportTrackBatch::dispatchSync($track) : ImportTrackBatch::dispatch($track);

        return $track;
    }
}
