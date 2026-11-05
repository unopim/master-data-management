<?php

namespace Webkul\MasterDataManagement\ValueObjects;

final readonly class SourceContext
{
    /**
     * The origin of a write a bulk import makes.
     */
    public const string IMPORT = 'import';

    /**
     * @param  string  $origin  Write channel, e.g. admin, api, import, seeder, pipeline.
     * @param  array<string, mixed>  $context  Kept on a change request the write opens, for the work its approval completes.
     */
    public function __construct(
        public string $origin,
        public ?int $sourceSystemId = null,
        public ?string $externalId = null,
        public array $context = [],
    ) {}

    /**
     * Whether a bulk import made the write.
     */
    public function isImport(): bool
    {
        return $this->origin === self::IMPORT;
    }
}
