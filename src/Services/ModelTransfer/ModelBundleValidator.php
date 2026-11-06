<?php

namespace Webkul\MasterDataManagement\Services\ModelTransfer;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidatorInstance;
use Webkul\Attribute\Rules\ValidationTypes;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\EntityTypeStatus;
use Webkul\MasterDataManagement\Enums\MasteringStyle;
use Webkul\MasterDataManagement\Enums\RelationCardinality;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Http\Requests\EntityTypeRequest;
use Webkul\MasterDataManagement\Http\Requests\FieldRequest;
use Webkul\MasterDataManagement\Matching\ComparatorRegistry;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\MatchRuleRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\SourceSystemRepository;
use Webkul\MasterDataManagement\Repositories\SurvivorshipRuleRepository;
use Webkul\MasterDataManagement\Services\SchemaPublisher;
use Webkul\MasterDataManagement\Survivorship\StrategyRegistry;

/**
 * Validates a whole model bundle before anything is written: its shape, every code it names (an entity type, code
 * list or source system must be in the bundle or on this install), and the changes the field and relation type
 * screens refuse on existing definitions, such as a new field type or a reference that records already use.
 */
class ModelBundleValidator
{
    public const int MAX_ENTITY_TYPES = 500;

    public const int MAX_CODE_LISTS = 500;

    public const int MAX_CODES = 10000;

    public const int MAX_FIELDS = 500;

    public const int MAX_RELATION_TYPES = 1000;

    public const string CODE_REGEX = '/^[a-z][a-z0-9_]{0,63}$/';

    public const string ENTITY_TYPE_CODE_REGEX = '/^[a-z][a-z0-9_]{1,63}$/';

    public const string LIST_CODE_REGEX = '/^[A-Za-z0-9][A-Za-z0-9_.\-]*$/';

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected CodeListRepository $codeListRepository,
        protected SourceSystemRepository $sourceSystemRepository,
        protected FieldRepository $fieldRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected RelationRepository $relationRepository,
        protected MatchRuleRepository $matchRuleRepository,
        protected SurvivorshipRuleRepository $survivorshipRuleRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected ComparatorRegistry $comparatorRegistry,
        protected StrategyRegistry $strategyRegistry,
    ) {}

    /**
     * Validate the bundle and return its validated data.
     *
     * @param  array<mixed>  $bundle
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $bundle): array
    {
        if (($bundle['format_version'] ?? null) !== ModelBundleExporter::FORMAT_VERSION) {
            throw ValidationException::withMessages(['format_version' => [trans('masterdata::app.model-transfer.errors.unsupported-version', [
                'version'   => is_scalar($bundle['format_version'] ?? null) ? (string) $bundle['format_version'] : '',
                'supported' => ModelBundleExporter::FORMAT_VERSION,
            ])]]);
        }

        $context = $this->context($bundle);

        $validator = Validator::make($bundle, $this->rules($context));

        $validator->after(fn (ValidatorInstance $validator) => $this->checkReferences($validator, $bundle, $context));

        return $validator->validate();
    }

    /**
     * The codes the bundle may name, from the bundle and the install, and the existing definitions it changes.
     *
     * @param  array<mixed>  $bundle
     * @return array{types: list<string>, lists: list<string>, systems: list<string>, fields: array<string, array<string, Field>>, relation_types: array<string, RelationType>}
     */
    protected function context(array $bundle): array
    {
        $typeCodes = $this->codesOf($bundle['entity_types'] ?? null);

        $listCodes = $this->codesOf($bundle['code_lists'] ?? null);

        $relationCodes = $this->codesOf($bundle['relation_types'] ?? null);

        $typeCodesById = $this->entityTypeRepository->getModel()->newQuery()->whereIn('code', $typeCodes)->pluck('code', 'id')->all();

        $fields = [];

        foreach ($this->fieldRepository->getModel()->newQuery()->whereIn('entity_type_id', array_keys($typeCodesById))->get() as $field) {
            $fields[(string) $typeCodesById[$field->entity_type_id]][(string) $field->code] = $field;
        }

        return [
            'types'          => array_values(array_unique([...$typeCodes, ...$this->entityTypeRepository->getModel()->newQuery()->pluck('code')->map(strval(...))->all()])),
            'lists'          => array_values(array_unique([...$listCodes, ...$this->codeListRepository->getModel()->newQuery()->pluck('code')->map(strval(...))->all()])),
            'systems'        => $this->sourceSystemRepository->getModel()->newQuery()->pluck('code')->map(strval(...))->all(),
            'fields'         => $fields,
            'relation_types' => $this->relationTypeRepository->getModel()->newQuery()->whereIn('code', $relationCodes)->get()->keyBy('code')->all(),
        ];
    }

    /**
     * The shape rules of the bundle.
     *
     * @param  array{types: list<string>, lists: list<string>, systems: list<string>}  $context
     * @return array<string, mixed>
     */
    protected function rules(array $context): array
    {
        $rules = [
            'format_version' => ['required', 'integer', Rule::in([ModelBundleExporter::FORMAT_VERSION])],

            'code_lists'                               => ['sometimes', 'array', 'max:'.self::MAX_CODE_LISTS],
            'code_lists.*'                             => ['array'],
            'code_lists.*.code'                        => ['required', 'string', 'regex:'.self::CODE_REGEX, 'distinct'],
            'code_lists.*.owner'                       => ['nullable', 'string', 'max:255'],
            'code_lists.*.is_versioned'                => ['sometimes', 'boolean'],
            'code_lists.*.translations'                => ['sometimes', 'array'],
            'code_lists.*.translations.*.name'         => ['nullable', 'string', 'max:255'],
            'code_lists.*.translations.*.description'  => ['nullable', 'string', 'max:2000'],
            'code_lists.*.codes'                       => ['sometimes', 'array', 'max:'.self::MAX_CODES],
            'code_lists.*.codes.*'                     => ['array'],
            'code_lists.*.codes.*.code'                => ['required', 'string', 'max:128', 'regex:'.self::LIST_CODE_REGEX],
            'code_lists.*.codes.*.parent_code'         => ['nullable', 'string', 'max:128'],
            'code_lists.*.codes.*.sort'                => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'code_lists.*.codes.*.is_active'           => ['sometimes', 'boolean'],
            'code_lists.*.codes.*.effective_from'      => ['nullable', 'date_format:Y-m-d'],
            'code_lists.*.codes.*.effective_to'        => ['nullable', 'date_format:Y-m-d'],
            'code_lists.*.codes.*.attributes'          => ['nullable', 'array'],
            'code_lists.*.codes.*.labels'              => ['sometimes', 'array'],
            'code_lists.*.codes.*.labels.*'            => ['nullable', 'string', 'max:255'],

            'entity_types'                                                                        => ['sometimes', 'array', 'max:'.self::MAX_ENTITY_TYPES],
            'entity_types.*'                                                                      => ['array'],
            'entity_types.*.code'                                                                 => ['required', 'string', 'regex:'.self::ENTITY_TYPE_CODE_REGEX, 'distinct'],
            'entity_types.*.translations'                                                         => ['sometimes', 'array'],
            'entity_types.*.translations.*.name'                                                  => ['nullable', 'string', 'max:255'],
            'entity_types.*.translations.*.description'                                           => ['nullable', 'string', 'max:2000'],
            'entity_types.*.icon'                                                                 => ['nullable', 'string', Rule::in(config('masterdata.entity_type_icons', []))],
            'entity_types.*.label_field_code'                                                     => ['nullable', 'string', 'max:64'],
            'entity_types.*.is_hierarchical'                                                      => ['sometimes', 'boolean'],
            'entity_types.*.is_scoped'                                                            => ['sometimes', 'boolean'],
            'entity_types.*.allowed_parent_types'                                                 => ['sometimes', 'nullable', 'array'],
            'entity_types.*.allowed_parent_types.*'                                               => ['string', Rule::in($context['types'])],
            'entity_types.*.approval_required'                                                    => ['sometimes', 'boolean'],
            'entity_types.*.mastering_style'                                                      => ['sometimes', Rule::enum(MasteringStyle::class)],
            'entity_types.*.code_strategy'                                                        => ['sometimes', Rule::enum(CodeStrategy::class)],
            'entity_types.*.code_pattern'                                                         => ['nullable', 'string', 'max:128'],
            'entity_types.*.status'                                                               => ['sometimes', Rule::enum(EntityTypeStatus::class)],
            'entity_types.*.sort_order'                                                           => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'entity_types.*.settings'                                                             => ['sometimes', 'nullable', 'array'],
            'entity_types.*.settings.'.ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEMS       => ['nullable', 'array'],
            'entity_types.*.settings.'.ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEMS.'.*'  => ['string', Rule::in($context['systems'])],
            'entity_types.*.settings.'.ModelBundleExporter::SETTING_REQUIRED_SOURCE_SYSTEM_IDS    => ['prohibited'],

            'entity_types.*.fields'                         => ['sometimes', 'array', 'max:'.self::MAX_FIELDS],
            'entity_types.*.fields.*'                       => ['array'],
            'entity_types.*.fields.*.code'                  => ['required', 'string', 'regex:'.self::CODE_REGEX],
            'entity_types.*.fields.*.type'                  => ['required', 'string', Rule::in($this->fieldTypeRegistry->codes())],
            'entity_types.*.fields.*.group'                 => ['nullable', 'string', 'max:64'],
            'entity_types.*.fields.*.position'              => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'entity_types.*.fields.*.validation'            => ['nullable', 'array:'.implode(',', FieldRequest::VALIDATION_KEYS)],
            'entity_types.*.fields.*.validation.type'       => ['nullable', Rule::in(ValidationTypes::VALIDATION_TYPES)],
            'entity_types.*.fields.*.validation.regex'      => ['nullable', 'string', 'max:255', $this->compilesRegex(...)],
            'entity_types.*.fields.*.validation.min'        => ['nullable', 'numeric'],
            'entity_types.*.fields.*.validation.max'        => ['nullable', 'numeric'],
            'entity_types.*.fields.*.validation.max_length' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'entity_types.*.fields.*.code_list'             => ['nullable', 'string', Rule::in($context['lists'])],
            'entity_types.*.fields.*.reference_entity_type' => ['nullable', 'string', Rule::in($context['types'])],
            'entity_types.*.fields.*.labels'                => ['sometimes', 'array'],
            'entity_types.*.fields.*.labels.*'              => ['nullable', 'string', 'max:255'],
            'entity_types.*.fields.*.help'                  => ['sometimes', 'array'],
            'entity_types.*.fields.*.help.*'                => ['nullable', 'string', 'max:2000'],

            'entity_types.*.match_rules'                     => ['sometimes', 'array', 'max:'.MatchRuleRepository::MAX_RULES],
            'entity_types.*.match_rules.*'                   => ['array'],
            'entity_types.*.match_rules.*.target'            => ['required', 'string', 'max:64'],
            'entity_types.*.match_rules.*.comparator'        => ['required', 'string', Rule::in($this->comparatorRegistry->codes())],
            'entity_types.*.match_rules.*.weight'            => ['required', 'numeric', 'between:0.1,100'],
            'entity_types.*.match_rules.*.is_blocking'       => ['sometimes', 'boolean'],
            'entity_types.*.match_rules.*.sort_order'        => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'entity_types.*.match_rules.*.options'           => ['sometimes', 'nullable', 'array'],
            'entity_types.*.match_rules.*.options.tolerance' => ['nullable', 'numeric', 'min:0'],
            'entity_types.*.match_rules.*.options.days'      => ['nullable', 'numeric', 'min:0'],

            'entity_types.*.survivorship_rules'                          => ['sometimes', 'array', 'max:'.SurvivorshipRuleRepository::MAX_RULES],
            'entity_types.*.survivorship_rules.*'                        => ['array'],
            'entity_types.*.survivorship_rules.*.target'                 => ['nullable', 'string', 'max:64'],
            'entity_types.*.survivorship_rules.*.strategy'               => ['required', 'string', Rule::in($this->strategyRegistry->codes())],
            'entity_types.*.survivorship_rules.*.sort_order'             => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'entity_types.*.survivorship_rules.*.options'                => ['sometimes', 'nullable', 'array'],
            'entity_types.*.survivorship_rules.*.options.source_order'   => ['nullable', 'array', 'max:100'],
            'entity_types.*.survivorship_rules.*.options.source_order.*' => ['string', Rule::in($context['systems'])],

            'relation_types'                    => ['sometimes', 'array', 'max:'.self::MAX_RELATION_TYPES],
            'relation_types.*'                  => ['array'],
            'relation_types.*.code'             => ['required', 'string', 'regex:'.self::CODE_REGEX, 'distinct'],
            'relation_types.*.from_entity_type' => ['required', 'string', Rule::in($context['types'])],
            'relation_types.*.to_entity_type'   => ['required', 'string', Rule::in($context['types'])],
            'relation_types.*.cardinality'      => ['required', Rule::enum(RelationCardinality::class)],
            'relation_types.*.labels'           => ['sometimes', 'array'],
            'relation_types.*.labels.*'         => ['nullable', 'string', 'max:255'],
        ];

        foreach (SchemaPublisher::FLAGS as $flag) {
            $rules['entity_types.*.fields.*.'.$flag] = ['sometimes', 'boolean'];
        }

        foreach (EntityTypeRequest::settingsRules() as $key => $settingRules) {
            $rules['entity_types.*.settings.'.$key] = $settingRules;
        }

        return $rules;
    }

    /**
     * Check what the shape rules cannot: codes unique within their parent, targets naming fields of their type, the
     * field and relation type changes the admin screens refuse, and the rule limits per type.
     *
     * @param  array<mixed>  $bundle
     * @param  array{types: list<string>, lists: list<string>, systems: list<string>, fields: array<string, array<string, Field>>, relation_types: array<string, RelationType>}  $context
     */
    protected function checkReferences(ValidatorInstance $validator, array $bundle, array $context): void
    {
        foreach ($this->listOf($bundle['code_lists'] ?? null) as $listIndex => $list) {
            $codes = $this->codesOf($list['codes'] ?? null);

            $seen = [];

            foreach ($this->listOf($list['codes'] ?? null) as $codeIndex => $code) {
                $key = "code_lists.$listIndex.codes.$codeIndex";

                $this->refuseDuplicate($validator, $key.'.code', $code['code'] ?? null, $seen);

                $parent = $code['parent_code'] ?? null;

                if (is_string($parent) && ($parent === ($code['code'] ?? null) || ! in_array($parent, $codes, true))) {
                    $validator->errors()->add($key.'.parent_code', trans('masterdata::app.model-transfer.errors.unknown-reference', ['value' => $parent]));
                }

                $from = $code['effective_from'] ?? null;
                $to = $code['effective_to'] ?? null;

                if (is_string($from) && is_string($to) && $to <= $from) {
                    $validator->errors()->add($key.'.effective_to', trans('masterdata::app.model-transfer.errors.effective-range', ['code' => (string) ($code['code'] ?? '')]));
                }
            }
        }

        foreach ($this->listOf($bundle['entity_types'] ?? null) as $typeIndex => $type) {
            $this->checkEntityType($validator, "entity_types.$typeIndex", $type, $context['fields'][(string) ($type['code'] ?? '')] ?? []);
        }

        foreach ($this->listOf($bundle['relation_types'] ?? null) as $index => $relationType) {
            $existing = $context['relation_types'][(string) ($relationType['code'] ?? '')] ?? null;

            if (! $existing instanceof RelationType) {
                continue;
            }

            if ($existing->is_system) {
                $validator->errors()->add("relation_types.$index.code", trans('masterdata::app.model-transfer.errors.system-relation', ['code' => $existing->code]));

                continue;
            }

            $existing->loadMissing(['fromEntityType:id,code', 'toEntityType:id,code']);

            $reshaped = $existing->fromEntityType?->code !== ($relationType['from_entity_type'] ?? null)
                || $existing->toEntityType?->code !== ($relationType['to_entity_type'] ?? null)
                || $existing->cardinality->value !== ($relationType['cardinality'] ?? null);

            if ($reshaped && $this->relationRepository->existsForType((int) $existing->id)) {
                $validator->errors()->add("relation_types.$index.code", trans('masterdata::app.model-transfer.errors.relation-locked', ['code' => $existing->code]));
            }
        }
    }

    /**
     * Check one entity type of the bundle against its existing fields.
     *
     * @param  array<string, mixed>  $type
     * @param  array<string, Field>  $existingFields
     */
    protected function checkEntityType(ValidatorInstance $validator, string $key, array $type, array $existingFields): void
    {
        $code = (string) ($type['code'] ?? '');

        if (($type['code_strategy'] ?? CodeStrategy::Pattern->value) === CodeStrategy::Pattern->value
            && preg_match('/\{seq(:\d{1,2})?\}/', (string) ($type['code_pattern'] ?? '{seq}')) !== 1) {
            $validator->errors()->add($key.'.code_pattern', trans('masterdata::app.model-transfer.errors.code-pattern', ['code' => $code]));
        }

        $fields = $this->listOf($type['fields'] ?? null);

        $fieldCodes = $this->codesOf($type['fields'] ?? null);

        $searchable = [];

        $seen = [];

        foreach ($fields as $index => $field) {
            $fieldKey = "$key.fields.$index";
            $fieldCode = (string) ($field['code'] ?? '');
            $fieldType = (string) ($field['type'] ?? '');

            $this->refuseDuplicate($validator, $fieldKey.'.code', $fieldCode, $seen);

            if (in_array($fieldType, FieldRequest::CODE_LIST_TYPES, true) && blank($field['code_list'] ?? null)) {
                $validator->errors()->add($fieldKey.'.code_list', trans('masterdata::app.model-transfer.errors.code-list-required', ['code' => $fieldCode]));
            }

            if ($fieldType === 'reference' && blank($field['reference_entity_type'] ?? null)) {
                $validator->errors()->add($fieldKey.'.reference_entity_type', trans('masterdata::app.model-transfer.errors.reference-required', ['code' => $fieldCode]));
            }

            if (! empty($field['is_searchable']) || ! empty($field['is_unique'])) {
                $searchable[] = $fieldCode;
            }

            $existing = $existingFields[$fieldCode] ?? null;

            if (! $existing instanceof Field) {
                continue;
            }

            if ($existing->type !== $fieldType) {
                $validator->errors()->add($fieldKey.'.type', trans('masterdata::app.model-transfer.errors.type-locked', ['code' => $fieldCode]));

                continue;
            }

            if ($fieldType === 'reference' && $this->referenceReshaped($existing, $field)
                && $this->relationRepository->fieldIdsWithRelations([(int) $existing->id]) !== []) {
                $validator->errors()->add($fieldKey.'.reference_entity_type', trans('masterdata::app.model-transfer.errors.reference-locked', ['code' => $fieldCode]));
            }
        }

        foreach ($existingFields as $existing) {
            if (! in_array($existing->code, $fieldCodes, true) && ! $existing->is_archived) {
                $fieldCodes[] = (string) $existing->code;

                if ($existing->is_searchable || $existing->is_unique) {
                    $searchable[] = (string) $existing->code;
                }
            }
        }

        $labelField = $type['label_field_code'] ?? null;

        if (is_string($labelField) && ! in_array($labelField, $fieldCodes, true)) {
            $validator->errors()->add($key.'.label_field_code', trans('masterdata::app.model-transfer.errors.unknown-reference', ['value' => $labelField]));
        }

        $this->checkMatchRules($validator, $key, $this->listOf($type['match_rules'] ?? null), $fieldCodes, $searchable);

        $this->checkSurvivorshipRules($validator, $key, $this->listOf($type['survivorship_rules'] ?? null), $fieldCodes);
    }

    /**
     * Match rules compare the record code, the label or a field of the type, once per comparator; a blocking rule
     * needs an indexed field.
     *
     * @param  array<int, array<string, mixed>>  $rules
     * @param  list<string>  $fieldCodes
     * @param  list<string>  $searchable
     */
    protected function checkMatchRules(ValidatorInstance $validator, string $key, array $rules, array $fieldCodes, array $searchable): void
    {
        $seen = [];

        foreach ($rules as $index => $rule) {
            $ruleKey = "$key.match_rules.$index";
            $target = (string) ($rule['target'] ?? '');
            $direct = in_array($target, [MatchRule::TARGET_CODE, MatchRule::TARGET_LABEL], true);

            if (! $direct && ! in_array($target, $fieldCodes, true)) {
                $validator->errors()->add($ruleKey.'.target', trans('masterdata::app.model-transfer.errors.unknown-reference', ['value' => $target]));
            }

            $pair = $target.'|'.($rule['comparator'] ?? '');

            if (isset($seen[$pair])) {
                $validator->errors()->add($ruleKey.'.comparator', trans('masterdata::app.model-transfer.errors.duplicate', ['value' => $pair]));
            }

            $seen[$pair] = true;

            if (! empty($rule['is_blocking']) && ! $direct && ! in_array($target, $searchable, true)) {
                $validator->errors()->add($ruleKey.'.is_blocking', trans('masterdata::app.match-rules.validation.blocking-not-searchable'));
            }
        }
    }

    /**
     * Survivorship rules decide the label, a field of the type or, without a target, every other field, once each.
     *
     * @param  array<int, array<string, mixed>>  $rules
     * @param  list<string>  $fieldCodes
     */
    protected function checkSurvivorshipRules(ValidatorInstance $validator, string $key, array $rules, array $fieldCodes): void
    {
        $seen = [];

        foreach ($rules as $index => $rule) {
            $target = $rule['target'] ?? null;

            if (is_string($target) && $target !== SurvivorshipRule::TARGET_LABEL && ! in_array($target, $fieldCodes, true)) {
                $validator->errors()->add("$key.survivorship_rules.$index.target", trans('masterdata::app.model-transfer.errors.unknown-reference', ['value' => $target]));
            }

            $slot = is_string($target) ? $target : SurvivorshipRule::TARGET_DEFAULT;

            if (isset($seen[$slot])) {
                $validator->errors()->add("$key.survivorship_rules.$index.target", trans('masterdata::app.model-transfer.errors.duplicate', ['value' => $slot]));
            }

            $seen[$slot] = true;
        }
    }

    /**
     * Whether the bundle field points an existing reference field elsewhere or changes how many records it holds.
     *
     * @param  array<string, mixed>  $field
     */
    protected function referenceReshaped(Field $existing, array $field): bool
    {
        $existing->loadMissing('referenceEntityType:id,code');

        return $existing->referenceEntityType?->code !== ($field['reference_entity_type'] ?? null)
            || (bool) $existing->multiple !== (bool) ($field['multiple'] ?? false);
    }

    /**
     * Refuse a code already used by an earlier entry of the same list, remembering the codes seen so far.
     *
     * @param  array<string, true>  $seen
     */
    protected function refuseDuplicate(ValidatorInstance $validator, string $key, mixed $code, array &$seen): void
    {
        if (! is_string($code)) {
            return;
        }

        if (isset($seen[$code])) {
            $validator->errors()->add($key, trans('masterdata::app.model-transfer.errors.duplicate', ['value' => $code]));
        }

        $seen[$code] = true;
    }

    /**
     * Refuse a validation pattern PHP cannot compile.
     */
    protected function compilesRegex(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && @preg_match($value, '') === false) {
            $fail('masterdata::app.fields.validation.invalid-regex')->translate();
        }
    }

    /**
     * The array entries of a list value of the bundle, keyed by their index.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function listOf(mixed $value): array
    {
        return is_array($value) ? array_filter($value, is_array(...)) : [];
    }

    /**
     * The string codes of a list of bundle entries, in order.
     *
     * @return list<string>
     */
    protected function codesOf(mixed $entries): array
    {
        return array_values(array_map(
            fn (array $entry): string => (string) $entry['code'],
            array_filter($this->listOf($entries), fn (array $entry): bool => is_string($entry['code'] ?? null)),
        ));
    }
}
