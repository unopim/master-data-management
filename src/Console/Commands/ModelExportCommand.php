<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleExporter;

#[Description('Export entity types, fields, relation types, code lists and rules as a JSON model bundle')]
#[Signature('masterdata:model:export
        {--types=* : The codes of the entity types to export; the whole model when left out}
        {--path= : The .json file to write; the bundle is printed when left out}')]
class ModelExportCommand extends Command
{
    /**
     * Write the model bundle to the file, or print it.
     */
    public function handle(ModelBundleExporter $exporter, EntityTypeRepository $entityTypeRepository): int
    {
        $codes = array_values(array_unique(array_map(strval(...), (array) $this->option('types'))));

        $unknown = array_diff($codes, $entityTypeRepository->getModel()->newQuery()->whereIn('code', $codes)->pluck('code')->map(strval(...))->all());

        if ($unknown !== []) {
            $this->components->error(trans('masterdata::app.model-transfer.console.unknown-types', ['codes' => implode(', ', $unknown)]));

            return self::FAILURE;
        }

        $bundle = $exporter->export($codes);

        $json = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $path = (string) $this->option('path');

        if ($path === '') {
            $this->line($json);

            return self::SUCCESS;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'json' || ! is_dir(dirname($path)) || file_put_contents($path, $json) === false) {
            $this->components->error(trans('masterdata::app.model-transfer.console.path-invalid', ['path' => $path]));

            return self::FAILURE;
        }

        $this->components->info(trans('masterdata::app.model-transfer.console.exported', [
            'types' => count($bundle['entity_types']),
            'lists' => count($bundle['code_lists']),
            'path'  => $path,
        ]));

        return self::SUCCESS;
    }
}
