<?php

namespace Webkul\MasterDataManagement\Services;

use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorInstance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Http\Requests\FieldRequest;

/**
 * Checks a domain template or shared code list against the format the template installer reads, before anything is
 * written, so a broken file fails as a whole instead of half installing.
 */
class TemplateValidator
{
    protected const CODE_PATTERN = '/^[a-z][a-z0-9_]*$/';

    public function __construct(
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected TemplateReader $templateReader,
    ) {}

    /**
     * @param  array<string, mixed>  $template
     *
     * @throws ValidationException
     */
    public function validateTemplate(array $template): void
    {
        $typeCodes = array_values(array_filter(array_column($template['entity_types'] ?? [], 'code'), is_string(...)));

        $field = 'entity_types.*.fields.*.';

        $validator = Validator::make($template, [
            'names'                                               => ['required', 'array'],
            'names.'.TemplateReader::SOURCE_LOCALE                => ['required', 'string', 'max:255'],
            'entity_types'                                        => ['required', 'array', 'min:1'],
            'entity_types.*.code'                                 => ['required', 'string', 'max:64', 'regex:'.self::CODE_PATTERN, 'distinct'],
            'entity_types.*.names'                                => ['required', 'array'],
            'entity_types.*.names.'.TemplateReader::SOURCE_LOCALE => ['required', 'string', 'max:255'],
            'entity_types.*.icon'                                 => ['nullable', 'string', Rule::in(config('masterdata.entity_type_icons', []))],
            'entity_types.*.label_field_code'                     => ['nullable', 'string', 'max:64'],
            'entity_types.*.is_hierarchical'                      => ['sometimes', 'boolean'],
            'entity_types.*.parents'                              => ['sometimes', 'array'],
            'entity_types.*.parents.*'                            => ['string', Rule::in($typeCodes)],
            'entity_types.*.code_strategy'                        => ['sometimes', Rule::enum(CodeStrategy::class)],
            'entity_types.*.code_pattern'                         => ['nullable', 'string', 'max:64'],
            'entity_types.*.status'                               => ['sometimes', Rule::enum(EntityTypeStatus::class)],
            'entity_types.*.fields'                               => ['sometimes', 'array'],
            $field.'code'                                         => ['required', 'string', 'max:64', 'regex:'.self::CODE_PATTERN],
            $field.'type'                                         => ['required', 'string', Rule::in($this->fieldTypeRegistry->codes())],
            $field.'group'                                        => ['nullable', 'string', 'max:64'],
            $field.'labels'                                       => ['required', 'array'],
            $field.'labels.'.TemplateReader::SOURCE_LOCALE        => ['required', 'string', 'max:255'],
            $field.'is_required'                                  => ['sometimes', 'boolean'],
            $field.'is_unique'                                    => ['sometimes', 'boolean'],
            $field.'is_identifier'                                => ['sometimes', 'boolean'],
            $field.'is_searchable'                                => ['sometimes', 'boolean'],
            $field.'value_per_locale'                             => ['sometimes', 'boolean'],
            $field.'code_list'                                    => ['required_if:'.$field.'type,'.implode(',', FieldRequest::CODE_LIST_TYPES), 'nullable', 'string', $this->shippedCodeList()],
            $field.'reference'                                    => ['required_if:'.$field.'type,reference', 'nullable', 'string', Rule::in($typeCodes)],
            $field.'validation'                                   => ['nullable', 'array', $this->knownValidationKeys()],
            'source_systems'                                      => ['sometimes', 'array'],
            'source_systems.*.code'                               => ['required', 'string', 'max:64', 'distinct'],
            'source_systems.*.name'                               => ['required', 'string', 'max:255'],
            'samples'                                             => ['sometimes', 'array'],
            'samples.*.entity_type'                               => ['required', 'string', Rule::in($typeCodes)],
            'samples.*.code'                                      => ['required', 'string', 'max:255'],
            'samples.*.parent'                                    => ['nullable', 'string'],
            'samples.*.references'                                => ['sometimes', 'array'],
            'samples.*.values'                                    => ['required', 'array'],
        ]);

        $validator->after(fn (ValidatorInstance $validator) => $this->checkFieldCodes($validator, $template['entity_types'] ?? []));

        $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $codeList
     *
     * @throws ValidationException
     */
    public function validateCodeList(array $codeList): void
    {
        Validator::make($codeList, [
            'code'                                          => ['required', 'string', 'max:64', 'regex:'.self::CODE_PATTERN],
            'names'                                         => ['required', 'array'],
            'names.'.TemplateReader::SOURCE_LOCALE          => ['required', 'string', 'max:255'],
            'codes'                                         => ['required', 'array', 'min:1'],
            'codes.*.code'                                  => ['required', 'string', 'max:128', 'distinct'],
            'codes.*.labels'                                => ['required', 'array'],
            'codes.*.labels.'.TemplateReader::SOURCE_LOCALE => ['required', 'string', 'max:255'],
        ])->validate();
    }

    /**
     * Field codes are unique within their entity type, and the label field is one of the type's fields.
     *
     * @param  array<int, mixed>  $types
     */
    protected function checkFieldCodes(ValidatorInstance $validator, array $types): void
    {
        foreach ($types as $index => $type) {
            if (! is_array($type) || ! is_array($type['fields'] ?? null) || $type['fields'] === []) {
                continue;
            }

            $codes = array_column($type['fields'], 'code');

            foreach (array_keys(array_diff_key($codes, array_unique($codes))) as $duplicate) {
                $validator->errors()->add("entity_types.{$index}.fields.{$duplicate}.code", trans('validation.distinct', ['attribute' => 'code']));
            }

            if (isset($type['label_field_code']) && ! in_array($type['label_field_code'], $codes, true)) {
                $validator->errors()->add("entity_types.{$index}.label_field_code", trans('validation.in', ['attribute' => 'label_field_code']));
            }
        }
    }

    protected function shippedCodeList(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! $this->templateReader->hasCodeList($value)) {
                $fail('validation.exists')->translate();
            }
        };
    }

    protected function knownValidationKeys(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_array($value) && array_diff(array_keys($value), FieldRequest::VALIDATION_KEYS) !== []) {
                $fail('validation.in')->translate();
            }
        };
    }
}
