<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Enums\CodeStrategy;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\MasterDataManagement\ValueObjects\SourceContext;

/**
 * Writes the sample records a template ships with, through the record writer so they pass the same validation as
 * any other write. A sample whose code already exists is skipped, so seeding twice adds nothing.
 */
class TemplateSampleInstaller
{
    public function __construct(
        protected RecordRepository $recordRepository,
        protected RecordWriter $recordWriter,
    ) {}

    /**
     * Create the missing samples in template order; parents and referenced records come first in the list.
     *
     * @param  list<array<string, mixed>>  $samples
     * @param  array<string, EntityType>  $types  Keyed by entity type code.
     * @param  array<string, array<string, mixed>>  $definitions  The template's entity type definitions by code.
     * @return int The number of records created.
     *
     * @throws ValidationException
     */
    public function install(array $samples, array $types, array $definitions): int
    {
        $source = new SourceContext('seeder');

        $created = 0;

        foreach ($samples as $sample) {
            $type = $types[$sample['entity_type']];

            if ($this->recordRepository->findByCode((int) $type->id, $sample['code']) instanceof Record) {
                continue;
            }

            $definition = $definitions[$sample['entity_type']];

            $input = ['values' => $this->withReferences($sample, $definition, $types)];

            if (isset($sample['parent'])) {
                $input['parent'] = $this->findParent($sample['parent'], $definition, $types)?->uuid;
            }

            if (! $this->buildsCode($definition)) {
                $input['code'] = $sample['code'];
            }

            $this->recordWriter->create($type, $input, null, $source);

            $created++;
        }

        return $created;
    }

    /**
     * The sample values with each reference given by record code replaced by that record's uuid.
     *
     * @param  array<string, mixed>  $sample
     * @param  array<string, mixed>  $definition
     * @param  array<string, EntityType>  $types
     * @return array<string, mixed>
     */
    protected function withReferences(array $sample, array $definition, array $types): array
    {
        $values = $sample['values'];

        $targets = array_column($definition['fields'] ?? [], 'reference', 'code');

        foreach ($sample['references'] ?? [] as $fieldCode => $code) {
            $targetType = $types[$targets[$fieldCode] ?? ''] ?? null;

            $record = $targetType instanceof EntityType ? $this->recordRepository->findByCode((int) $targetType->id, (string) $code) : null;

            $values['common'][$fieldCode] = $record?->uuid;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, EntityType>  $types
     */
    protected function findParent(string $code, array $definition, array $types): ?Record
    {
        foreach ($definition['parents'] ?? [] as $parentType) {
            $record = isset($types[$parentType]) ? $this->recordRepository->findByCode((int) $types[$parentType]->id, $code) : null;

            if ($record instanceof Record) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Whether the type builds record codes itself, from a pattern or its identifier fields, rather than taking them.
     *
     * @param  array<string, mixed>  $definition
     */
    protected function buildsCode(array $definition): bool
    {
        return ($definition['code_strategy'] ?? null) === CodeStrategy::Pattern->value
            || in_array(true, array_column($definition['fields'] ?? [], 'is_identifier'), true);
    }
}
