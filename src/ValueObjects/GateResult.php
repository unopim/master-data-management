<?php

namespace Webkul\MasterDataManagement\ValueObjects;

final readonly class GateResult
{
    private function __construct(
        public bool $applyNow,
        public ?string $reference = null,
    ) {}

    /**
     * The change may be written straight away.
     */
    public static function apply(): self
    {
        return new self(true);
    }

    /**
     * The change is held for approval under the given change request reference.
     */
    public static function pending(string $reference): self
    {
        return new self(false, $reference);
    }

    /**
     * Whether the change is held for approval.
     */
    public function isPending(): bool
    {
        return ! $this->applyNow;
    }
}
