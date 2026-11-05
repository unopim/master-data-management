<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Webkul\MasterDataManagement\Events\SchemaPublished;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\EntityTypeVersion;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Repositories\EntityTypeVersionRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\User\Models\Admin;

/**
 * Publishes an entity type's schema as a numbered, immutable snapshot of its fields.
 */
class SchemaPublisher
{
    /**
     * The field flags a snapshot records.
     */
    public const FLAGS = [
        'is_required',
        'is_unique',
        'is_identifier',
        'value_per_locale',
        'is_effective_dated',
        'is_searchable',
        'multiple',
        'is_archived',
    ];

    public function __construct(
        protected EntityTypeRepository $entityTypeRepository,
        protected EntityTypeVersionRepository $entityTypeVersionRepository,
        protected FieldRepository $fieldRepository,
    ) {}

    /**
     * Bump the type's schema version and write the snapshot, under a row lock so concurrent publishes get distinct versions.
     *
     * The snapshot refers to code lists and reference types by code, not id, so it stays portable across installs.
     * Once committed, SchemaPublished queues a quality scan of the type's records.
     */
    public function publish(EntityType $type, ?Admin $by = null): EntityTypeVersion
    {
        $version = DB::transaction(function () use ($type, $by): EntityTypeVersion {
            $locked = $this->entityTypeRepository->findForUpdate($type->id);

            $locked->schema_version += 1;

            $locked->save();

            $type->setAttribute('schema_version', $locked->schema_version)->syncOriginalAttribute('schema_version');

            return $this->entityTypeVersionRepository->create([
                'entity_type_id' => $locked->id,
                'version'        => $locked->schema_version,
                'fields'         => $this->fieldRepository->allFor($locked->id)
                    ->map(fn (Field $field): array => $this->snapshot($field))
                    ->values()
                    ->all(),
                'published_by' => $by?->id,
                'published_at' => now(),
            ]);
        });

        event(new SchemaPublished($type, $version));

        return $version;
    }

    /**
     * The snapshot of one field.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Field $field): array
    {
        $flags = [];

        foreach (self::FLAGS as $flag) {
            $flags[$flag] = $field->getAttribute($flag);
        }

        return [
            'code'                  => $field->code,
            'type'                  => $field->type,
            'group'                 => $field->group,
            'position'              => $field->position,
            'flags'                 => $flags,
            'validation'            => $field->validation,
            'code_list'             => $field->codeList?->code,
            'reference_entity_type' => $field->referenceEntityType?->code,
            'labels'                => $field->translations
                ->filter(fn ($translation): bool => filled($translation->label))
                ->mapWithKeys(fn ($translation): array => [$translation->locale => $translation->label])
                ->all(),
        ];
    }
}
