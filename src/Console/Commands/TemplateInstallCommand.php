<?php

namespace Webkul\MasterDataManagement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Webkul\Core\Platform\ModuleGate;
use Webkul\MasterDataManagement\Services\TemplateInstaller;
use Webkul\MasterDataManagement\Services\TemplateReader;
use Webkul\MasterDataManagement\Support\Platform;

#[Description('Install master data domain templates (customer, supplier, employee, location, product reference) with their shared code lists')]
#[Signature('masterdata:templates:install
    {templates?* : The templates to install, e.g. customer supplier}
    {--all : Install every template}
    {--samples : Also add the sample records of each template}
    {--list : List the available templates}')]
class TemplateInstallCommand extends Command
{
    /**
     * Install the named templates; installing again keeps what exists and adds only what is missing.
     */
    public function handle(TemplateInstaller $installer, TemplateReader $reader, ModuleGate $moduleGate): int
    {
        $available = $reader->available();

        if ($this->option('list')) {
            $this->table(
                [trans('masterdata::app.templates.template'), trans('masterdata::app.templates.label')],
                array_map(fn (array $option): array => [$option['id'], $option['label']], $installer->options(app()->getLocale())),
            );

            return self::SUCCESS;
        }

        $names = $this->option('all') ? $available : array_values(array_unique((array) $this->argument('templates')));

        if ($names === []) {
            $this->components->error(trans('masterdata::app.templates.choose', ['names' => implode(', ', $available)]));

            return self::FAILURE;
        }

        if (! $moduleGate->enabled(Platform::MODULE)) {
            $this->components->warn(trans('masterdata::app.demo.module-disabled'));
        }

        foreach ($names as $name) {
            try {
                $totals = $installer->install($name, (bool) $this->option('samples'));
            } catch (InvalidArgumentException $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            } catch (ValidationException $exception) {
                $this->components->error(implode(' ', $exception->validator->errors()->all()));

                return self::FAILURE;
            }

            $this->components->info(trans('masterdata::app.templates.installed', [
                'name'    => $name,
                'types'   => $totals['entity_types'],
                'fields'  => $totals['fields'],
                'lists'   => $totals['code_lists'],
                'records' => $totals['records'],
            ]));
        }

        return self::SUCCESS;
    }
}
