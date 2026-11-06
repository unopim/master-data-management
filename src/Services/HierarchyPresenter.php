<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Collection;
use Webkul\MasterDataManagement\Models\Hierarchy;
use Webkul\MasterDataManagement\Models\HierarchyNode;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\HierarchyNodeRepository;
use Webkul\MasterDataManagement\Repositories\HierarchyRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\Admin;

/**
 * Shapes hierarchies for the admin pages and the API: a record's alternate placements and one level of a tree.
 */
class HierarchyPresenter
{
    /**
     * The most nodes one tree level lists.
     */
    public const int TREE_LIMIT = 100;

    public function __construct(
        protected HierarchyRepository $hierarchyRepository,
        protected HierarchyNodeRepository $hierarchyNodeRepository,
        protected RecordRepository $recordRepository,
        protected RecordPresenter $recordPresenter,
    ) {}

    /**
     * The record's place in each alternate hierarchy of its entity type: the hierarchy, whether it is placed, the
     * ancestors the admin may see from the root down and the parent as the picker's initial option.
     *
     * Two queries cover every hierarchy: the record's nodes and the ancestor records.
     *
     * @return list<array{hierarchy: Hierarchy, placed: bool, trail: list<array{uuid: string, code: string, label: string}>, parent: array{id: string, label: string}}>
     */
    public function placements(Record $record, ?Admin $admin = null): array
    {
        $hierarchies = $this->hierarchyRepository->alternatesFor((int) $record->entity_type_id);

        if ($hierarchies->isEmpty()) {
            return [];
        }

        $nodes = $this->hierarchyNodeRepository->forRecords([(int) $record->id])->keyBy('hierarchy_id');

        $chains = $nodes->map(fn (HierarchyNode $node): array => $this->ancestorIds($node));

        $visible = $this->recordRepository->summaries($chains->flatten()->unique()->map(intval(...))->values()->all(), $admin);

        $type = (string) ($record->entityType?->name ?: $record->entityType?->code);

        return $hierarchies->map(function (Hierarchy $hierarchy) use ($nodes, $chains, $visible, $type): array {
            $node = $nodes->get($hierarchy->id);

            $trail = array_values(array_map(
                fn (int $id): array => ['uuid' => $visible[$id]['uuid'], 'code' => $visible[$id]['code'], 'label' => $visible[$id]['label']],
                array_filter($chains->get($hierarchy->id, []), fn (int $id): bool => isset($visible[$id])),
            ));

            return [
                'hierarchy' => $hierarchy,
                'placed'    => $node instanceof HierarchyNode,
                'trail'     => $trail,
                'parent'    => $this->parentOption($node?->parent_id, $visible, $type),
            ];
        })->values()->all();
    }

    /**
     * One level of a hierarchy tree: the roots, or the children of a record, that the admin may see, each with its
     * child count, in at most four queries.
     *
     * @return array{nodes: list<array{uuid: string, code: string, label: string, status: string, url: string, children: int}>, limited: bool}
     */
    public function level(Hierarchy $hierarchy, ?Record $parent, ?Admin $admin = null): array
    {
        $nodes = $parent instanceof Record
            ? $this->hierarchyNodeRepository->childrenOf((int) $hierarchy->id, (int) $parent->id, self::TREE_LIMIT)
            : $this->hierarchyNodeRepository->rootsOf((int) $hierarchy->id, self::TREE_LIMIT);

        $visible = $this->recordRepository->summaries($nodes->pluck('record_id')->map(intval(...))->all(), $admin);

        $counts = $this->hierarchyNodeRepository->childCounts((int) $hierarchy->id, array_keys($visible));

        return [
            'nodes' => $nodes
                ->filter(fn (HierarchyNode $node): bool => isset($visible[$node->record_id]))
                ->map(fn (HierarchyNode $node): array => [
                    'uuid'     => $visible[$node->record_id]['uuid'],
                    'code'     => $visible[$node->record_id]['code'],
                    'label'    => $visible[$node->record_id]['label'],
                    'status'   => trans('masterdata::app.records.statuses.'.$visible[$node->record_id]['status']),
                    'url'      => route('admin.masterdata.records.edit', $visible[$node->record_id]['uuid']),
                    'children' => $counts[$node->record_id] ?? 0,
                ])
                ->values()
                ->all(),
            'limited' => $nodes->count() >= self::TREE_LIMIT,
        ];
    }

    /**
     * The hierarchies of the entity type as selector options, keyed by id.
     *
     * @param  Collection<int, Hierarchy>  $hierarchies
     * @return list<array{id: int, label: string}>
     */
    public function options(Collection $hierarchies): array
    {
        return $hierarchies->map(fn (Hierarchy $hierarchy): array => ['id' => (int) $hierarchy->id, 'label' => $hierarchy->displayLabel()])->values()->all();
    }

    /**
     * The picker option of a placement's parent: the root, the parent itself, or a neutral label for a parent the
     * admin may not see, which keeps its uuid so saving unchanged keeps it.
     *
     * @param  array<int, array{id: int, uuid: string, code: string, label: string, status: string, entity_type_id: int, parent_id: ?int}>  $visible
     * @return array{id: string, label: string}
     */
    protected function parentOption(?int $parentId, array $visible, string $type): array
    {
        if ($parentId === null) {
            return $this->recordPresenter->rootOption();
        }

        if (isset($visible[$parentId])) {
            $parent = $visible[$parentId];

            return ['id' => $parent['uuid'], 'label' => trans($parent['label'] === '' ? 'masterdata::app.records.option-label-no-label' : 'masterdata::app.records.option-label', [
                'code'  => $parent['code'],
                'label' => $parent['label'],
                'type'  => $type,
            ])];
        }

        $uuid = $this->recordRepository->hierarchyRow($parentId)?->uuid;

        return $uuid === null ? $this->recordPresenter->rootOption() : ['id' => (string) $uuid, 'label' => trans('masterdata::app.hierarchy.hidden-parent')];
    }

    /**
     * The record ids on the node's path above it, root first.
     *
     * @return list<int>
     */
    protected function ancestorIds(HierarchyNode $node): array
    {
        return array_values(array_diff(
            array_map(intval(...), array_filter(explode('/', (string) $node->path), is_numeric(...))),
            [(int) $node->record_id],
        ));
    }
}
