<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleImporter;

#[Description('Import a JSON model bundle as draft schema, optionally publishing it')]
#[Signature('masterdata:model:import
        {path : The .json model bundle to import}
        {--publish : Publish the changed entity type schemas and code lists}')]
class ModelImportCommand extends Command
{
    /**
     * Validate and import the bundle, listing every validation error when it is refused.
     */
    public function handle(ModelBundleImporter $importer): int
    {
        $path = (string) $this->argument('path');

        $real = realpath($path);

        if ($real === false || ! is_file($real) || ! is_readable($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'json'
            || filesize($real) > (int) config('masterdata.model_transfer.max_file_kb', 5120) * 1024) {
            $this->components->error(trans('masterdata::app.model-transfer.console.path-invalid', ['path' => $path]));

            return self::FAILURE;
        }

        try {
            $result = $importer->import($importer->decode((string) file_get_contents($real), 'path'), (bool) $this->option('publish'));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $errors) {
                foreach ($errors as $error) {
                    $this->components->error(trans('masterdata::app.model-transfer.errors.at-path', ['path' => $key, 'message' => $error]));
                }
            }

            return self::FAILURE;
        }

        $this->components->info(trans('masterdata::app.model-transfer.console.imported', ['path' => $path] + $result->totals()));

        return self::SUCCESS;
    }
}
