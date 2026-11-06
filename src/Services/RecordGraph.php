<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Enums\XrefStatus;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\Repositories\RelationRepository;
use Webkul\MasterDataManagement\Repositories\RelationTypeRepository;
use Webkul\MasterDataManagement\Repositories\XrefRepository;
use Webkul\User\Models\Admin;

/**
 * The neighbourhood of a record as nodes and edges: its relations both ways, its parent and children, and its active
 * source ids, up to two hops out.
 *
 * Each hop costs three queries whatever its fan-out (relations, children, the visible neighbours), and the graph
 * closes with one query each for the source ids, relation type labels and entity type names. A neighbour the admin
 * may not see is left out together with every edge to it, and no hop expands through it. Nodes stop at the limit,
 * nearest hop first, and the result says when some were left out.
 */
class RecordGraph
{
    /**
     * The edge kinds a graph draws.
     */
    public const string EDGE_RELATION = 'relation';

    public const string EDGE_PARENT = 'parent';

    public const string EDGE_SOURCE = 'source';

    public function __construct(
        protected RecordRepository $recordRepository,
        protected RelationRepository $relationRepository,
        protected RelationTypeRepository $relationTypeRepository,
        protected XrefRepository $xrefRepository,
        protected EntityTypeRepository $entityTypeRepository,
    ) {}

    /**
     * The graph around the record, `hops` steps out and at most `limit` nodes including the record itself.
     *
     * @return array{center: string, nodes: list<array<string, mixed>>, edges: list<array{source: string, target: string, kind: string, label: string}>, truncated: bool}
     */
    public function around(Record $center, int $hops, int $limit, ?Admin $admin = null): array
    {
        $nodes = [(int) $center->id => [
            'id'             => (int) $center->id,
            'uuid'           => (string) $center->uuid,
            'code'           => (string) $center->code,
            'label'          => (string) $center->label,
            'status'         => (string) $center->status?->value,
            'entity_type_id' => (int) $center->entity_type_id,
            'parent_id'      => $center->parent_id,
            'hop'            => 0,
        ]];

        $links = [];

        $truncated = false;

        $frontier = [(int) $center->id];

        for ($hop = 1; $hop <= $hops && $frontier !== [] && ! $truncated; $hop++) {
            [$found, $capped] = $this->links($frontier, $nodes, $limit);

            $truncated = $capped;

            array_push($links, ...$found);

            $candidates = [];

            foreach ($found as [$from, $to]) {
                foreach ([$from, $to] as $id) {
                    if (! isset($nodes[$id])) {
                        $candidates[$id] = true;
                    }
                }
            }

            $visible = $this->recordRepository->summaries(array_keys($candidates), $admin);

            ksort($visible);

            $frontier = [];

            foreach ($visible as $id => $summary) {
                if (count($nodes) >= $limit) {
                    $truncated = true;

                    break;
                }

                $nodes[$id] = $summary + ['hop' => $hop];

                $frontier[] = $id;
            }
        }

        return $this->shape($center, $nodes, $links, $limit, $truncated);
    }

    /**
     * The relation and parent links touching the frontier, each as [from id, to id, kind, relation type id], and
     * whether a query reached its cap and so may have left links out.
     *
     * @param  list<int>  $frontier
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array{0: list<array{0: int, 1: int, 2: string, 3: ?int}>, 1: bool}
     */
    protected function links(array $frontier, array $nodes, int $limit): array
    {
        $cap = $limit * 4;

        $links = [];

        $relations = $this->relationRepository->edgesTouching($frontier, $cap);

        $children = $this->recordRepository->childLinks($frontier, $cap);

        foreach ($relations as $edge) {
            $links[] = [(int) $edge->from_record_id, (int) $edge->to_record_id, self::EDGE_RELATION, (int) $edge->relation_type_id];
        }

        foreach ($frontier as $id) {
            $parentId = $nodes[$id]['parent_id'] ?? null;

            if ($parentId !== null) {
                $links[] = [(int) $parentId, $id, self::EDGE_PARENT, null];
            }
        }

        foreach ($children as [$childId, $parentId]) {
            $links[] = [$parentId, $childId, self::EDGE_PARENT, null];
        }

        return [$links, count($relations) >= $cap || count($children) >= $cap];
    }

    /**
     * Turn the collected nodes and links into the response, with the record's source ids as leaf nodes.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  list<array{0: int, 1: int, 2: string, 3: ?int}>  $links
     * @return array{center: string, nodes: list<array<string, mixed>>, edges: list<array{source: string, target: string, kind: string, label: string}>, truncated: bool}
     */
    protected function shape(Record $center, array $nodes, array $links, int $limit, bool $truncated): array
    {
        $relationTypeIds = array_values(array_unique(array_filter(array_column($links, 3))));

        $relationLabels = $this->relationTypeRepository->labelsByIds($relationTypeIds);

        $typeNames = $this->entityTypeRepository->namesByIds(array_values(array_unique(array_column($nodes, 'entity_type_id'))));

        $result = [];

        foreach ($nodes as $node) {
            $result[] = [
                'id'     => $node['uuid'],
                'code'   => $node['code'],
                'label'  => $node['label'],
                'type'   => $typeNames[$node['entity_type_id']] ?? '',
                'status' => $node['status'] === '' ? '' : trans('masterdata::app.records.statuses.'.$node['status']),
                'kind'   => 'record',
                'hop'    => $node['hop'],
                'url'    => route('admin.masterdata.records.edit', $node['uuid']),
            ];
        }

        $edges = [];

        foreach ($links as [$from, $to, $kind, $relationTypeId]) {
            if (! isset($nodes[$from], $nodes[$to]) || $from === $to) {
                continue;
            }

            $edges[$kind.':'.$from.':'.$to.':'.$relationTypeId] = [
                'source' => $nodes[$from]['uuid'],
                'target' => $nodes[$to]['uuid'],
                'kind'   => $kind,
                'label'  => $kind === self::EDGE_PARENT ? trans('masterdata::app.graph.edges.parent') : ($relationLabels[$relationTypeId] ?? ''),
            ];
        }

        foreach ($this->xrefRepository->entriesForRecord((int) $center->id) as $xref) {
            if ($xref->status !== XrefStatus::Active->value) {
                continue;
            }

            if (count($result) >= $limit) {
                $truncated = true;

                break;
            }

            $id = 'xref-'.$xref->id;

            $result[] = [
                'id'     => $id,
                'code'   => (string) $xref->external_id,
                'label'  => (string) $xref->name,
                'type'   => (string) $xref->code,
                'status' => '',
                'kind'   => self::EDGE_SOURCE,
                'hop'    => 1,
                'url'    => null,
            ];

            $edges[] = [
                'source' => (string) $center->uuid,
                'target' => $id,
                'kind'   => self::EDGE_SOURCE,
                'label'  => trans('masterdata::app.graph.edges.source'),
            ];
        }

        return [
            'center'    => (string) $center->uuid,
            'nodes'     => $result,
            'edges'     => array_values($edges),
            'truncated' => $truncated,
        ];
    }
}
