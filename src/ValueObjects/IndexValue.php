<?php

namespace Webkul\MasterDataManagement\ValueObjects;

/**
 * The searchable form of one field value. The text is raw; the indexer normalises it.
 */
final readonly class IndexValue
{
    public function __construct(
        public ?string $text = null,
        public ?string $numeric = null,
        public ?string $date = null,
    ) {}
}
