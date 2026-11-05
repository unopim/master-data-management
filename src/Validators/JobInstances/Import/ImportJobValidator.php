<?php

namespace Webkul\MasterDataManagement\Validators\JobInstances\Import;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Rules\SeparatorTypes;
use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;
use Webkul\User\Models\Admin;

/**
 * The import settings every master data importer shares: a CSV, XLS or XLSX file, the append action only, the
 * validation strategy and the filters of the importer.
 */
abstract class ImportJobValidator extends JobValidator
{
    /**
     * The filter rules of the importer, keyed by filter name.
     *
     * @return array<string, mixed>
     */
    abstract protected function filterRules(): array;

    /**
     * The translated names of the importer's filters, keyed by filter name.
     *
     * @return array<string, string>
     */
    abstract protected function filterNames(): array;

    /**
     * {@inheritdoc}
     */
    public function getRules(array $options): array
    {
        $rules = [
            'action'              => ['required', 'in:'.Import::ACTION_APPEND],
            'validation_strategy' => ['required', Rule::in($this->validationStrategies())],
            'allowed_errors'      => ['required', 'integer', 'min:0'],
            'field_separator'     => ['required', new SeparatorTypes],
            'file'                => [empty($options['id']) ? 'required' : 'nullable', 'mimes:csv,txt,xlsx,xls', 'extensions:csv,xlsx,xls'],
        ];

        foreach ($this->filterRules() as $name => $filterRules) {
            $rules['filters.'.$name] = $filterRules;
        }

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeNames(array $options): array
    {
        $names = [
            'action'              => trans('admin::app.settings.data-transfer.imports.create.action'),
            'validation_strategy' => trans('admin::app.settings.data-transfer.imports.create.validation-strategy'),
            'allowed_errors'      => trans('admin::app.settings.data-transfer.imports.create.allowed-errors'),
            'field_separator'     => trans('admin::app.settings.data-transfer.imports.create.field-separator'),
            'file'                => trans('admin::app.settings.data-transfer.imports.create.file'),
        ];

        foreach ($this->filterNames() as $name => $label) {
            $names['filters.'.$name] = $label;
        }

        return $names;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessages(array $options): array
    {
        return [
            'action.in'  => trans('masterdata::app.imports.errors.delete-not-supported'),
            'file.mimes' => trans('validation.mimes', ['values' => 'csv,xls,xlsx']),
        ];
    }

    /**
     * The validation strategies the import form offers, and the importer's own spelling of them.
     *
     * @return list<string>
     */
    protected function validationStrategies(): array
    {
        return array_values(array_unique([
            ...array_map(strval(...), array_column((array) config('import_settings.validation_strategy', []), 'id')),
            Import::VALIDATION_STRATEGY_STOP_ON_ERROR,
            Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        ]));
    }

    /**
     * The admin saving the job, when there is one.
     */
    protected function admin(): ?Admin
    {
        $admin = auth()->guard('admin')->user();

        return $admin instanceof Admin ? $admin : null;
    }

    /**
     * Refuse one filter with a translated message.
     *
     * @throws ValidationException
     */
    protected function fail(string $filter, string $message): never
    {
        throw ValidationException::withMessages(['filters['.$filter.']' => [$message]]);
    }

    /**
     * One filter value of the submitted data as a trimmed string.
     *
     * @param  array<string, mixed>  $data
     */
    protected function filterValue(array $data, string $name): string
    {
        $value = ((array) ($data['filters'] ?? []))[$name] ?? null;

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
