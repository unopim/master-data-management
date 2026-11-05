<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Gate;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Http\Requests\RecordParentRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;

/**
 * Shapes a record for the admin pages: its picker option, the hierarchy panel and the flattened value rows.
 */
class RecordPresenter
{
    /**
     * The most children the record page lists.
     */
    public const int HIERARCHY_LIMIT = 100;

    /**
     * The most relation types the relations panel names as possible.
     */
    public const int RELATION_TYPES_LIMIT = 50;

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordFormBuilder $recordFormBuilder,
        protected RelationRepository $relationRepository,
        protected HierarchyService $hierarchyService,
        protected RelationTypeRepository $relationTypeRepository,
    ) {}

    /**
     * The record page's hierarchy panel: ancestors, the first children and, for editors, the current parent as the
     * async picker's initial option; null for a record outside any hierarchy.
     *
     * @return array{ancestors: Collection<int, Record>, children: Collection<int, Record>, limit: int, canMove: bool, currentParent: array{id: string, label: string}}|null
     */
    public function hierarchy(Record $record): ?array
    {
        $type = $record->entityType;

        if (! $type->is_hierarchical && $record->parent_id === null) {
            return null;
        }

        $ancestors = $this->recordRepository->ancestorsOf($record);

        return [
            'ancestors'     => $ancestors,
            'children'      => $this->recordRepository->childrenOf($record, self::HIERARCHY_LIMIT),
            'limit'         => self::HIERARCHY_LIMIT,
            'canMove'       => $type->is_hierarchical
                && in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true)
                && Gate::allows('update', $record),
            'currentParent' => $this->currentParentOption($record, $ancestors->firstWhere('id', $record->parent_id)),
        ];
    }

    /**
     * The record page's relations panel: the records this one points at and the records pointing at it, limited to
     * the records the admin may see, and the labels of the relation types possible in each direction.
     *
     * @return array{outbound: list<array{type: string, uuid: string, code: string, label: string}>, inbound: list<array{type: string, uuid: string, code: string, label: string}>, types: array{outbound: list<string>, inbound: list<string>}}
     */
    public function relations(Record $record): array
    {
        $outbound = $this->relationRepository->outbound($record);

        $inbound = $this->relationRepository->inbound($record);

        $visible = array_flip($this->recordRepository->visibleUuids(
            $outbound->pluck('toRecord.uuid')->merge($inbound->pluck('fromRecord.uuid'))->filter()->unique()->map(strval(...))->values()->all(),
        ));

        $rows = fn ($relations, string $relation): array => $relations
            ->filter(fn ($item): bool => $item->{$relation} !== null && isset($visible[$item->{$relation}->uuid]))
            ->map(fn ($item): array => [
                'type'  => (string) ($item->relationType?->label ?: ''),
                'uuid'  => (string) $item->{$relation}->uuid,
                'code'  => (string) $item->{$relation}->code,
                'label' => (string) $item->{$relation}->label,
            ])
            ->values()
            ->all();

        $types = $this->relationTypeRepository->touchingEntityType((int) $record->entity_type_id, self::RELATION_TYPES_LIMIT);

        $labels = fn (string $column): array => $types
            ->filter(fn (RelationType $type): bool => (int) $type->{$column} === (int) $record->entity_type_id)
            ->map(fn (RelationType $type): string => (string) ($type->label ?: $type->code))
            ->values()
            ->all();

        return [
            'outbound' => $rows($outbound, 'toRecord'),
            'inbound'  => $rows($inbound, 'fromRecord'),
            'types'    => ['outbound' => $labels('from_entity_type_id'), 'inbound' => $labels('to_entity_type_id')],
        ];
    }

    /**
     * The picker option that places the record at the root of its hierarchy.
     *
     * @return array{id: string, label: string}
     */
    public function rootOption(): array
    {
        return ['id' => RecordParentRequest::ROOT, 'label' => trans('masterdata::app.hierarchy.no-parent')];
    }

    /**
     * The URL of the entity type's record grid filtered to the direct children of the parent, or to the top level
     * for the root sentinel.
     *
     * The grid restores filters from `filters[column][]` query keys, so the brackets stay empty.
     */
    public function childrenUrl(EntityType $entityType, string $parentUuid): string
    {
        return route('admin.masterdata.records.index', $entityType->code)
            .'?'.rawurlencode('filters[parent][]').'='.rawurlencode($parentUuid);
    }

    /**
     * Links to the grids of the given hierarchical entity types whose records may sit under the record.
     *
     * @param  BaseCollection<int, EntityType>  $entityTypes
     * @return list<array{label: string, name: string, url: string}>
     */
    public function childTypeLinks(Record $record, BaseCollection $entityTypes): array
    {
        return $this->childTypes((int) $record->entity_type_id, $entityTypes)
            ->map(fn (EntityType $type): array => [
                'label' => trans('masterdata::app.hierarchy.browse-children', ['type' => $type->name ?: $type->code]),
                'name'  => (string) ($type->name ?: $type->code),
                'url'   => $this->childrenUrl($type, (string) $record->uuid),
            ])
            ->values()
            ->all();
    }

    /**
     * The given hierarchical entity types whose records may sit directly under records of the type.
     *
     * @param  BaseCollection<int, EntityType>  $entityTypes
     * @return BaseCollection<int, EntityType>
     */
    public function childTypes(int $entityTypeId, BaseCollection $entityTypes): BaseCollection
    {
        return $entityTypes
            ->filter(fn (EntityType $type): bool => $type->is_hierarchical
                && in_array($entityTypeId, $this->hierarchyService->allowedParentTypeIds($type), true))
            ->values();
    }

    /**
     * The record as a picker option of its uuid and label.
     *
     * @return array{id: string, label: string}
     */
    public function option(Record $record): array
    {
        return ['id' => (string) $record->uuid, 'label' => $this->optionLabel($record)];
    }

    /**
     * Label a record for a select: code, label and entity type.
     */
    public function optionLabel(Record $record): string
    {
        $type = (string) ($record->entityType?->name ?: $record->entityType?->code);

        $label = (string) $record->label;

        return trans($label === '' ? 'masterdata::app.records.option-label-no-label' : 'masterdata::app.records.option-label', [
            'code'  => (string) $record->code,
            'label' => $label,
            'type'  => $type,
        ]);
    }

    /**
     * Flatten a record's values into locale, attribute and display rows.
     *
     * @param  array<string, mixed>  $values
     * @return list<array{locale: ?string, key: string, value: string}>
     */
    public function valueRows(array $values): array
    {
        $rows = [];

        foreach ($values['common'] ?? [] as $key => $value) {
            $rows[] = ['locale' => null, 'key' => (string) $key, 'value' => $this->recordFormBuilder->display($value)];
        }

        foreach ($values['locale_specific'] ?? [] as $locale => $localeValues) {
            foreach ($localeValues as $key => $value) {
                $rows[] = ['locale' => (string) $locale, 'key' => (string) $key, 'value' => $this->recordFormBuilder->display($value)];
            }
        }

        return $rows;
    }

    /**
     * One side of a merge comparison as display text: the common value, then each locale's value prefixed by it.
     *
     * @param  array<string, mixed>  $side
     */
    public function mergeSide(array $side): string
    {
        $parts = [];

        foreach ($side as $key => $value) {
            $text = $this->recordFormBuilder->display($value);

            if ($text !== '') {
                $parts[] = $key === 'common' ? $text : $key.': '.$text;
            }
        }

        return implode(' · ', $parts);
    }

    /**
     * The picker option of the record's current parent: the root, the parent itself, or a neutral label for a parent
     * the admin may not see, which keeps its uuid so saving unchanged keeps it.
     *
     * @return array{id: string, label: string}
     */
    protected function currentParentOption(Record $record, ?Record $parent): array
    {
        if ($record->parent_id === null) {
            return $this->rootOption();
        }

        if ($parent instanceof Record) {
            return $this->option($parent);
        }

        $uuid = $this->recordRepository->hierarchyRow($record->parent_id)?->uuid;

        return $uuid === null
            ? $this->rootOption()
            : ['id' => (string) $uuid, 'label' => trans('masterdata::app.hierarchy.hidden-parent')];
    }
}
