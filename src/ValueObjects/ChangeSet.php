<?php

namespace Webkul\MasterDataManagement\ValueObjects;

use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\User\Models\Admin;

final readonly class ChangeSet
{
    /**
     * @param  array<string, mixed>  $before  Record attributes before the change; empty on create.
     * @param  array<string, mixed>  $after  Record attributes the change proposes.
     * @param  array<string, mixed>  $input  The writer input that produced the change, replayed when it is approved.
     * @param  bool  $automated  Whether a rule decided the change rather than its author, so no author's rights skip approval.
     * @param  ?Record  $parent  The record a create is placed below, against which the author's approve grants are checked.
     */
    public function __construct(
        public ChangeOperation $operation,
        public EntityType $entityType,
        public ?Record $record,
        public array $before,
        public array $after,
        public ?Admin $author = null,
        public ?SourceContext $source = null,
        public ?int $baseRevision = null,
        public array $input = [],
        public bool $automated = false,
        public ?Record $parent = null,
    ) {}
}
