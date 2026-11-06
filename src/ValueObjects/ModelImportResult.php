<?php

namespace Webkul\MasterDataManagement\ValueObjects;

/**
 * What a model bundle import wrote: definitions created and updated per kind, and the codes of the entity types and
 * code lists it published.
 */
final class ModelImportResult
{
    public const array KINDS = ['code_lists', 'codes', 'entity_types', 'fields', 'relation_types', 'match_rules', 'survivorship_rules'];

    /**
     * @var array<string, int>
     */
    public array $created;

    /**
     * @var array<string, int>
     */
    public array $updated;

    /**
     * @var array{entity_types: list<string>, code_lists: list<string>}
     */
    public array $published = ['entity_types' => [], 'code_lists' => []];

    public function __construct()
    {
        $this->created = array_fill_keys(self::KINDS, 0);

        $this->updated = array_fill_keys(self::KINDS, 0);
    }

    /**
     * Count one created definition of the kind.
     */
    public function created(string $kind): void
    {
        $this->created[$kind]++;
    }

    /**
     * Count one updated definition of the kind.
     */
    public function updated(string $kind): void
    {
        $this->updated[$kind]++;
    }

    /**
     * Record a published entity type or code list.
     *
     * @param  'entity_types'|'code_lists'  $kind
     */
    public function published(string $kind, string $code): void
    {
        $this->published[$kind][] = $code;
    }

    /**
     * The totals of created, updated and published definitions.
     *
     * @return array{created: int, updated: int, published: int}
     */
    public function totals(): array
    {
        return [
            'created'   => array_sum($this->created),
            'updated'   => array_sum($this->updated),
            'published' => count($this->published['entity_types']) + count($this->published['code_lists']),
        ];
    }
}
