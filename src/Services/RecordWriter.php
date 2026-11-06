<?php

namespace Webkul\MasterDataManagement\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Contracts\ChangeGate;
use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Enums\GrantAction;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Events\RecordCreated;
use Webkul\MasterDataManagement\Events\RecordRetired;
use Webkul\MasterDataManagement\Events\RecordUpdated;
use Webkul\MasterDataManagement\Exceptions\StaleRecordException;
use Webkul\MasterDataManagement\FieldTypes\FieldTypeRegistry;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordProxy;
use Webkul\MasterDataManagement\Quality\CompletenessCalculator;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Security\AccessGrantResolver;
use Webkul\MasterDataManagement\Standardization\FieldTransformer;
use Webkul\MasterDataManagement\ValueObjects\ChangeSet;
use Webkul\MasterDataManagement\ValueObjects\GateResult;
use Webkul\MasterDataManagement\ValueObjects\RecordWriteResult;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;
use Webkul\User\Models\Admin;

class RecordWriter
{
    public function __construct(
        protected RecordRepository $recordRepository,
        protected EntityTypeRepository $entityTypeRepository,
        protected ChangeGate $changeGate,
        protected RecordIndexer $recordIndexer,
        protected FieldRuleBuilder $fieldRuleBuilder,
        protected FieldRepository $fieldRepository,
        protected FieldTypeRegistry $fieldTypeRegistry,
        protected HierarchyService $hierarchyService,
        protected CompletenessCalculator $completenessCalculator,
        protected RelationWriter $relationWriter,
        protected RelationRepository $relationRepository,
        protected AccessGrantResolver $accessGrantResolver,
        protected FieldTransformer $fieldTransformer,
    ) {}

    /**
     * The value rule keys that only allow the type's field codes; checked against the input, not stored values.
     */
    public const array KEY_RULES = ['values.common', 'values.locale_specific.*'];

    /**
     * Validate and create a record of the given entity type.
     *
     * When the change gate holds the change, an unsaved record carrying the proposed attributes is returned.
     * A code taken by a concurrent create between validation and insert is reported as a validation error.
     * An optional `parent` uuid places the record below that record, else it starts a tree of its own.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(EntityType $type, array $input, ?Admin $by, ?SourceContext $source = null): Record
    {
        return $this->submitCreate($type, $input, $by, $source)->record;
    }

    /**
     * Validate and create a record of the given entity type, reporting whether the change gate held it.
     *
     * The values are standardized by their field transforms first, so the rules check, and the record keeps, the
     * standard form.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function submitCreate(EntityType $type, array $input, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        $input = $this->fieldTransformer->transformInput($type, $input);

        $data = $this->validate($type, $input, null, [], $by);

        $parent = $this->resolveParent($data, $by);

        $this->ensurePlaceable($type, $parent, $by);

        if ($parent instanceof Record) {
            $this->hierarchyService->ensureValidParent($type, $parent);
        }

        [$data, $references] = $this->prepareValues($type, $data);

        $values = $this->mergeValues([], $data['values'] ?? []);

        $after = [
            'entity_type_id' => $type->id,
            'code'           => $data['code'] ?? $this->identifierCode($type, $values),
            'label'          => $this->resolveLabel($type, $values, $data['label'] ?? null),
            'values'         => $values,
            'status'         => $data['status'] ?? RecordStatus::Active->value,
            'schema_version' => $type->schema_version,
        ];

        $proposed = $after + ['parent_id' => $parent?->id] + ($references === [] ? [] : ['references' => $this->referenceSnapshot($references)]);

        $changeSet = new ChangeSet(ChangeOperation::Create, $type, null, [], $proposed, $by, $source, null, $input, parent: $parent);

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            $class = RecordProxy::modelClass();

            return new RecordWriteResult(new $class($after), $gate);
        }

        $code = $after['code'];

        try {
            $record = DB::transaction(function () use ($type, $after, $by, $parent, $references, &$code): Record {
                $after['code'] = $code ??= $this->entityTypeRepository->nextCode($type);

                $record = $this->recordRepository->create($after + [
                    'completeness' => $this->completenessCalculator->score($type, $after['values']),
                    'created_by'   => $by?->id,
                    'updated_by'   => $by?->id,
                ]);

                $this->hierarchyService->placeNew($record, $parent);

                $this->relationWriter->syncReferences($record, $references);

                $this->recordIndexer->index($record);

                return $record;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw_if($code === null || ! $this->recordRepository->codeExists($type->id, $code), $exception);

            throw ValidationException::withMessages(['code' => trans('validation.unique', ['attribute' => trans('masterdata::app.records.fields.code')])]);
        }

        event(new RecordCreated($record, $changeSet));

        return new RecordWriteResult($record, $gate);
    }

    /**
     * Validate and apply a partial update, guarded by the revision the caller read.
     *
     * When the change gate holds the change, the record is returned unchanged. A `parent` key (uuid, or null for
     * the root) that differs from the current parent moves the record under the same revision claim.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function update(Record $record, array $input, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): Record
    {
        return $this->submitUpdate($record, $input, $expectedRevision, $by, $source)->record;
    }

    /**
     * Validate and apply a partial update at the revision the caller read, reporting whether the change gate held it.
     *
     * An update that changes nothing is neither written nor held, so it bumps no revision and opens no change request.
     * The values are standardized by their field transforms before they are validated.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function submitUpdate(Record $record, array $input, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        $this->ensureEditable($record);

        $type = $record->entityType;

        $input = $this->fieldTransformer->transformInput($type, $input);

        $currentReferences = $this->currentReferences($record);

        $data = $this->validate($type, $this->keepHiddenReferences($type, $input, $currentReferences, $by), $record, $currentReferences, $by);

        $parentGiven = array_key_exists('parent', $data);

        $parent = $parentGiven ? $this->resolveParent($data, $by, $record->parent_id) : null;

        $moves = $parentGiven && $parent?->id !== $record->parent_id;

        if ($moves) {
            $this->ensurePlaceable($type, $parent, $by);

            $this->hierarchyService->ensureValidParent($type, $parent, $record);
        }

        [$data, $references] = $this->prepareValues($type, $data);

        $before = $this->snapshot($record, $currentReferences);

        $changeSet = new ChangeSet(
            ChangeOperation::Update,
            $type,
            $record,
            $before,
            array_replace(
                $before,
                $this->changesFor($type, $record, $data),
                $moves ? ['parent_id' => $parent?->id] : [],
                ['references' => $this->referenceSnapshot(array_replace($currentReferences, $references))],
            ),
            $by,
            $source,
            $expectedRevision,
            $input,
        );

        if ($this->normalized($changeSet->after) === $this->normalized($before)) {
            return new RecordWriteResult($record, GateResult::apply());
        }

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            return new RecordWriteResult($record, $gate);
        }

        $record = $this->persist($record, $expectedRevision, function (Record $fresh) use ($type, $data, $by, $moves, $parent, $references): void {
            if ($moves) {
                $this->hierarchyService->reparent($fresh, $parent);
            }

            $this->relationWriter->syncReferences($fresh, $references);

            $fresh->fill($this->changesFor($type, $fresh, $data) + ['updated_by' => $by?->id]);

            $fresh->completeness = $this->completenessCalculator->score($type, $fresh->values ?? []);
        });

        event(new RecordUpdated($record, $changeSet));

        return new RecordWriteResult($record, $gate);
    }

    /**
     * Retire a record, guarded by the revision the caller read.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function retire(Record $record, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): Record
    {
        return $this->submitRetire($record, $expectedRevision, $by, $source)->record;
    }

    /**
     * Retire a record at the revision the caller read, reporting whether the change gate held it.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function submitRetire(Record $record, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        $this->ensureEditable($record);

        $before = $this->snapshot($record, $this->currentReferences($record));

        $changeSet = new ChangeSet(
            ChangeOperation::Retire,
            $record->entityType,
            $record,
            $before,
            array_replace($before, ['status' => RecordStatus::Retired->value]),
            $by,
            $source,
            $expectedRevision,
        );

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            return new RecordWriteResult($record, $gate);
        }

        $record = $this->persist($record, $expectedRevision, function (Record $fresh) use ($by): void {
            $fresh->fill(['status' => RecordStatus::Retired, 'updated_by' => $by?->id]);
        });

        event(new RecordRetired($record, $changeSet));

        return new RecordWriteResult($record, $gate);
    }

    /**
     * Bring a retired record back to active, guarded by the revision the caller read.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function reactivate(Record $record, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): Record
    {
        return $this->submitReactivate($record, $expectedRevision, $by, $source)->record;
    }

    /**
     * Bring a retired record back to active at the revision the caller read, reporting whether the change gate held it.
     *
     * Only a retired record comes back, and only while its parent is not retired, so no active record hangs below a
     * retired one.
     *
     * @throws ValidationException
     * @throws StaleRecordException
     */
    public function submitReactivate(Record $record, int $expectedRevision, ?Admin $by, ?SourceContext $source = null): RecordWriteResult
    {
        if ($record->status !== RecordStatus::Retired) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.records.reactivate.not-retired')]);
        }

        if ($record->parent_id !== null && $this->recordRepository->statusOf((int) $record->parent_id) === RecordStatus::Retired) {
            throw ValidationException::withMessages(['parent' => trans('masterdata::app.records.reactivate.parent-retired')]);
        }

        $before = $this->snapshot($record, $this->currentReferences($record));

        $changeSet = new ChangeSet(
            ChangeOperation::Reactivate,
            $record->entityType,
            $record,
            $before,
            array_replace($before, ['status' => RecordStatus::Active->value]),
            $by,
            $source,
            $expectedRevision,
        );

        $gate = $this->changeGate->submit($changeSet);

        if ($gate->isPending()) {
            return new RecordWriteResult($record, $gate);
        }

        $record = $this->persist($record, $expectedRevision, function (Record $fresh) use ($by): void {
            $fresh->fill(['status' => RecordStatus::Active, 'updated_by' => $by?->id]);
        });

        event(new RecordUpdated($record, $changeSet));

        return new RecordWriteResult($record, $gate);
    }

    /**
     * Claim the expected revision, then apply the change to the freshly read row in the same transaction.
     *
     * The claim holds the row lock until commit, so the change is applied on top of the latest stored values.
     */
    protected function persist(Record $record, int $expectedRevision, Closure $apply): Record
    {
        return DB::transaction(function () use ($record, $expectedRevision, $apply): Record {
            throw_unless($this->recordRepository->claimRevision($record, $expectedRevision), StaleRecordException::class, $record, $expectedRevision);

            $record->refresh();

            $apply($record);

            $record->save();

            $this->recordIndexer->sync($record);

            return $record;
        });
    }

    /**
     * Refuse changes to records that left the active lifecycle.
     *
     * @throws ValidationException
     */
    protected function ensureEditable(Record $record): void
    {
        if (in_array($record->status, [RecordStatus::Retired, RecordStatus::Merged, RecordStatus::Erased], true)) {
            throw ValidationException::withMessages(['status' => trans('masterdata::app.records.not-editable')]);
        }
    }

    /**
     * Validate the write input for the entity type, then the values it leaves on the record against the type's fields.
     *
     * Unknown value keys are checked on the input only, so values of archived fields already stored stay editable;
     * the field rules run on the stored values and references merged with the input, so `required` holds on partial
     * updates. New reference targets must also be records the author may see.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, list<string>>  $currentReferences
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validate(EntityType $type, array $input, ?Record $record = null, array $currentReferences = [], ?Admin $by = null): array
    {
        $fieldRules = $this->fieldRuleBuilder->rulesFor($type, $record);

        $keyRules = Arr::only($fieldRules, self::KEY_RULES);

        $data = Validator::make($input, [
            'code'                     => $this->codeRules($type, $record),
            'label'                    => ['sometimes', 'nullable', 'string', 'max:255'],
            'status'                   => ['sometimes', Rule::enum(RecordStatus::class)->only([RecordStatus::Draft, RecordStatus::Active])],
            'values'                   => ['sometimes', 'array:common,locale_specific'],
            'values.common'            => ['sometimes', 'array', ...($keyRules['values.common'] ?? [])],
            'values.locale_specific'   => ['sometimes', 'array'],
            'values.locale_specific.*' => ['array', ...($keyRules['values.locale_specific.*'] ?? [])],
            'parent'                   => ['sometimes', 'nullable', 'uuid'],
        ])->validate();

        if ($fieldRules !== []) {
            Validator::make(
                ['values' => $this->mergeValues($this->storedValues($type, $record, $currentReferences), $data['values'] ?? [])],
                Arr::except($fieldRules, self::KEY_RULES),
                [],
                $this->fieldRuleBuilder->fieldLabels($type),
            )->validate();

            $this->ensureVisibleReferences($type, (array) ($data['values']['common'] ?? []), $currentReferences, $by);
        }

        return $data;
    }

    /**
     * Refuse reference targets the author may not see, with the message of a missing record so the error does not
     * tell a hidden record from an unknown one. Targets already stored on the record are not re-checked.
     *
     * @param  array<array-key, mixed>  $common
     * @param  array<string, list<string>>  $currentReferences
     *
     * @throws ValidationException
     */
    protected function ensureVisibleReferences(EntityType $type, array $common, array $currentReferences, ?Admin $by): void
    {
        $incoming = [];

        foreach ($this->referenceFields($type) as $field) {
            if (! array_key_exists($field->code, $common)) {
                continue;
            }

            $uuids = array_map(strtolower(...), array_filter((array) $common[$field->code], is_string(...)));

            $incoming[$field->code] = array_values(array_diff($uuids, $currentReferences[$field->code] ?? []));
        }

        $all = array_values(array_unique(array_merge([], ...array_values($incoming))));

        if ($all === []) {
            return;
        }

        $visible = array_flip($this->recordRepository->visibleUuids($all, $by));

        $labels = $this->fieldRuleBuilder->fieldLabels($type);

        $errors = [];

        foreach ($incoming as $code => $uuids) {
            foreach ($uuids as $uuid) {
                if (! isset($visible[$uuid])) {
                    $errors['values.common.'.$code] = trans('validation.exists', ['attribute' => $labels['values.common.'.$code] ?? $code]);

                    break;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Put back the stored reference targets the author may not see, which the author's screens never show, into
     * the submitted reference values: a `many` field adds them to the list, a `one` field keeps its hidden target
     * unless a new one is chosen. The input is otherwise returned as given.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, list<string>>  $currentReferences
     * @return array<string, mixed>
     */
    protected function keepHiddenReferences(EntityType $type, array $input, array $currentReferences, ?Admin $by): array
    {
        $common = $input['values']['common'] ?? null;

        if ($currentReferences === [] || ! is_array($common)) {
            return $input;
        }

        $fields = $this->referenceFields($type)
            ->filter(fn (Field $field): bool => array_key_exists($field->code, $common) && isset($currentReferences[$field->code]))
            ->keyBy('code');

        $stored = array_values(array_unique(array_merge([], ...array_values(array_intersect_key($currentReferences, $fields->all())))));

        if ($stored === []) {
            return $input;
        }

        $visible = array_flip($this->recordRepository->visibleUuids($stored, $by));

        foreach ($fields as $code => $field) {
            $hidden = array_values(array_filter($currentReferences[$code], fn (string $uuid): bool => ! isset($visible[$uuid])));

            if ($hidden === []) {
                continue;
            }

            $submitted = array_values(array_filter((array) $common[$code], fn (mixed $uuid): bool => is_string($uuid) && $uuid !== ''));

            if ($field->multiple) {
                $input['values']['common'][$code] = array_values(array_unique([...$submitted, ...$hidden]));
            } elseif ($submitted === []) {
                $input['values']['common'][$code] = $hidden[0];
            }
        }

        return $input;
    }

    /**
     * Refuse to place a record of a scoped type outside the author's edit subtrees, at the root included.
     *
     * @throws ValidationException
     */
    protected function ensurePlaceable(EntityType $type, ?Record $parent, ?Admin $by): void
    {
        if ($by instanceof Admin && ! $this->accessGrantResolver->allowsUnder($by, GrantAction::Edit, (int) $type->id, $parent)) {
            throw ValidationException::withMessages(['parent' => trans('masterdata::app.access.validation.parent-out-of-scope')]);
        }
    }

    /**
     * Resolve the validated `parent` uuid to a record the author may see; null when absent or null.
     *
     * The current parent is accepted unchecked, so resubmitting it is never a move even when its type is hidden.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function resolveParent(array $data, ?Admin $by, ?int $currentParentId = null): ?Record
    {
        if (! isset($data['parent'])) {
            return null;
        }

        $parent = $this->recordRepository->findByUuid($data['parent'], $by);

        if ($parent instanceof Record && $currentParentId !== null && $parent->id === $currentParentId) {
            return $parent;
        }

        if (! $parent instanceof Record || ($by instanceof Admin && Gate::forUser($by)->denies('view', $parent))) {
            throw ValidationException::withMessages(['parent' => trans('masterdata::app.hierarchy.validation.parent-not-found')]);
        }

        return $parent;
    }

    /**
     * Build the code rules: codes never change, pattern and identifier codes are generated, other strategies take
     * the caller's code.
     *
     * @return array<int, mixed>
     */
    protected function codeRules(EntityType $type, ?Record $record): array
    {
        if ($record instanceof Record) {
            return ['sometimes', Rule::in([$record->code])];
        }

        if ($type->code_strategy === CodeStrategy::Pattern || $this->fieldRuleBuilder->identifierFields($type)->isNotEmpty()) {
            return ['prohibited'];
        }

        return $this->callerCodeRules($type);
    }

    /**
     * The rules of a code taken from the caller or built from identifier values.
     *
     * @return array<int, mixed>
     */
    protected function callerCodeRules(EntityType $type): array
    {
        return [
            'required',
            'string',
            'max:255',
            Rule::unique(RecordProxy::modelClass(), 'code')->where('entity_type_id', $type->id),
        ];
    }

    /**
     * Build the code from the identifier fields' values in position order, joined with `-`; null without them.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws ValidationException
     */
    protected function identifierCode(EntityType $type, array $values): ?string
    {
        $fields = $this->fieldRuleBuilder->identifierFields($type);

        if ($fields->isEmpty()) {
            return null;
        }

        $locale = $this->fieldRuleBuilder->defaultLocale();

        $code = $fields
            ->map(fn (Field $field): mixed => $field->value_per_locale
                ? ($values['locale_specific'][$locale][$field->code] ?? null)
                : ($values['common'][$field->code] ?? null))
            ->map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '')
            ->implode('-');

        Validator::make(['code' => $code], ['code' => $this->callerCodeRules($type)], [], [
            'code' => trans('masterdata::app.records.fields.code'),
        ])->validate();

        return $code;
    }

    /**
     * Cast the validated values into their stored form and take out the values kept outside the values JSON.
     *
     * Returns the data and the reference values as field code => target uuids, which the relation writer stores;
     * references are never per locale, so a per-locale one is dropped. Keys that match no active field (types
     * without fields, archived fields) are kept as given.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, list<string>>}
     */
    protected function prepareValues(EntityType $type, array $data): array
    {
        if (! is_array($data['values'] ?? null)) {
            return [$data, []];
        }

        $fields = $this->fieldRepository->activeFor($type->id)->keyBy('code');

        $values = $data['values'];

        $references = [];

        if (is_array($values['common'] ?? null)) {
            [$values['common'], $references['common']] = $this->prepareSection($fields, $values['common']);
        }

        foreach ($values['locale_specific'] ?? [] as $locale => $localeValues) {
            [$values['locale_specific'][$locale]] = $this->prepareSection($fields, $localeValues);
        }

        $data['values'] = $values;

        return [$data, $references['common'] ?? []];
    }

    /**
     * Cast one `code => value` section and split it into stored values and the uuid lists kept as relations.
     *
     * @param  Collection<string, Field>  $fields
     * @param  array<array-key, mixed>  $section
     * @return array{0: array<array-key, mixed>, 1: array<string, list<string>>}
     */
    protected function prepareSection(Collection $fields, array $section): array
    {
        $references = [];

        foreach ($section as $code => $raw) {
            $field = $fields->get($code);

            if (! $field instanceof Field || ! $this->fieldTypeRegistry->has($field->type)) {
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->get($field->type);

            if ($fieldType->storesInValues()) {
                $section[$code] = $raw === null ? null : $fieldType->cast($raw, $field);

                continue;
            }

            $references[(string) $code] = array_values(array_map(strval(...), (array) ($raw === null ? null : $fieldType->cast($raw, $field))));

            unset($section[$code]);
        }

        return [$section, $references];
    }

    /**
     * Compute the attributes an update writes onto the given record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function changesFor(EntityType $type, Record $record, array $data): array
    {
        $values = array_key_exists('values', $data)
            ? $this->mergeValues($record->values ?? [], $data['values'])
            : ($record->values ?? []);

        $changes = [
            'label'          => $this->resolveLabel($type, $values, array_key_exists('label', $data) ? $data['label'] : $record->label),
            'values'         => $values,
            'schema_version' => $type->schema_version,
        ];

        if (isset($data['status'])) {
            $changes['status'] = $data['status'];
        }

        return $changes;
    }

    /**
     * Merge incoming values over stored ones per section, field by field.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    protected function mergeValues(array $current, array $incoming): array
    {
        if (array_key_exists('common', $incoming)) {
            $current['common'] = array_replace($current['common'] ?? [], $incoming['common']);
        }

        foreach ($incoming['locale_specific'] ?? [] as $locale => $fields) {
            $current['locale_specific'][$locale] = array_replace($current['locale_specific'][$locale] ?? [], $fields);
        }

        return $current;
    }

    /**
     * Take the label from the type's label field when it holds a value, else the explicit label.
     *
     * Only an active field of the type drives the label, the one the form lets the editor change; while it does,
     * an explicit label only fills in for an empty field value.
     *
     * @param  array<string, mixed>  $values
     */
    public function resolveLabel(EntityType $type, array $values, ?string $explicit): ?string
    {
        $code = $type->label_field_code;

        $value = $code && $this->fieldRepository->activeFor($type->id)->contains('code', $code)
            ? ($values['common'][$code] ?? null)
            : null;

        if (is_scalar($value) && (string) $value !== '') {
            return mb_substr((string) $value, 0, 255);
        }

        return $explicit;
    }

    /**
     * Sort the keys of every associative array, keeping list order, so two snapshots compare strictly.
     *
     * Both sides hold values in their stored form: incoming values are cast by the field types first, so the
     * strict comparison sees a type change (0 to null, "007" to "7") as the change it is.
     *
     * @param  array<array-key, mixed>  $attributes
     * @return array<array-key, mixed>
     */
    protected function normalized(array $attributes): array
    {
        if (! array_is_list($attributes)) {
            ksort($attributes, SORT_STRING);
        }

        return array_map(fn (mixed $value): mixed => is_array($value) ? $this->normalized($value) : $value, $attributes);
    }

    /**
     * Capture the record attributes and references a change set compares.
     *
     * @param  array<string, list<string>>  $references
     * @return array<string, mixed>
     */
    protected function snapshot(Record $record, array $references): array
    {
        return [
            'entity_type_id' => $record->entity_type_id,
            'code'           => $record->code,
            'label'          => $record->label,
            'parent_id'      => $record->parent_id,
            'values'         => $record->values ?? [],
            'status'         => $record->status?->value,
            'schema_version' => $record->schema_version,
            'references'     => $this->referenceSnapshot($references),
        ];
    }

    /**
     * The record's stored reference values, as field code => target uuids; no query for a type without references.
     *
     * @return array<string, list<string>>
     */
    protected function currentReferences(Record $record): array
    {
        if ($this->referenceFields($record->entityType)->isEmpty()) {
            return [];
        }

        return $this->relationRepository->referenceUuids([(int) $record->id])[(int) $record->id] ?? [];
    }

    /**
     * The stored values with the stored references in the input shape of their fields, for the field rules.
     *
     * @param  array<string, list<string>>  $references
     * @return array<string, mixed>
     */
    protected function storedValues(EntityType $type, ?Record $record, array $references): array
    {
        $values = $record?->values ?? [];

        foreach ($this->referenceFields($type) as $field) {
            if (isset($references[$field->code])) {
                $values['common'][$field->code] = $field->multiple ? $references[$field->code] : ($references[$field->code][0] ?? null);
            }
        }

        return $values;
    }

    /**
     * The active fields of the type whose values are kept outside the values JSON.
     *
     * @return Collection<int, Field>
     */
    protected function referenceFields(EntityType $type): Collection
    {
        return $this->fieldRepository->activeFor($type->id)->filter(
            fn (Field $field): bool => $this->fieldTypeRegistry->has($field->type) && ! $this->fieldTypeRegistry->get($field->type)->storesInValues()
        );
    }

    /**
     * References in a comparable form: each list sorted, empty lists dropped, keyed in field code order.
     *
     * @param  array<string, list<string>>  $references
     * @return array<string, list<string>>
     */
    protected function referenceSnapshot(array $references): array
    {
        $references = array_filter($references, fn (array $uuids): bool => $uuids !== []);

        foreach ($references as $code => $uuids) {
            sort($uuids, SORT_STRING);

            $references[$code] = $uuids;
        }

        ksort($references, SORT_STRING);

        return $references;
    }
}
