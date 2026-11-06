<?php

namespace Webkul\MasterDataManagement\Enums;

use Carbon\CarbonInterface;

/**
 * How long an open steward task has been waiting.
 */
enum WorkloadAgeBucket: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Older = 'older';

    /**
     * The age in days the bucket starts at.
     */
    public function fromDays(): int
    {
        return match ($this) {
            self::Day   => 0,
            self::Week  => 1,
            self::Month => 7,
            self::Older => 30,
        };
    }

    /**
     * The age in days the next bucket starts at, or null for the oldest bucket.
     */
    public function toDays(): ?int
    {
        return match ($this) {
            self::Day   => 1,
            self::Week  => 7,
            self::Month => 30,
            self::Older => null,
        };
    }

    /**
     * The creation times bounding the bucket as of the given moment: created at or before the first and after the
     * second, each left open when null, so the newest bucket also holds tasks stamped slightly ahead of the clock.
     *
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    public function window(CarbonInterface $now): array
    {
        $from = $this->fromDays();

        $to = $this->toDays();

        return [$from === 0 ? null : $now->copy()->subDays($from), $to === null ? null : $now->copy()->subDays($to)];
    }

    /**
     * The translated name of the bucket.
     */
    public function label(): string
    {
        return trans('masterdata::app.quality-dashboard.workload.buckets.'.$this->value);
    }
}
