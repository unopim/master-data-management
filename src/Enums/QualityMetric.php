<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * A percentage a quality snapshot tracks, named by its snapshot column.
 */
enum QualityMetric: string
{
    case Completeness = 'completeness_avg';
    case Validity = 'validity_rate';
    case Duplicates = 'duplicate_rate';

    /**
     * The translated name of the metric.
     */
    public function label(): string
    {
        return trans('masterdata::app.quality-dashboard.metrics.'.$this->value);
    }

    /**
     * Whether a rise of the metric is an improvement.
     */
    public function higherIsBetter(): bool
    {
        return $this !== self::Duplicates;
    }

    /**
     * The theme chart token the metric is drawn with.
     */
    public function chartToken(): string
    {
        return match ($this) {
            self::Completeness => '--chart-1',
            self::Validity     => '--chart-3',
            self::Duplicates   => '--chart-4',
        };
    }

    /**
     * The change of the metric between two snapshots, or null when either lacks a value.
     *
     * @param  array<string, mixed>  $latest
     * @param  array<string, mixed>|null  $previous
     */
    public function delta(array $latest, ?array $previous): ?float
    {
        $now = $latest[$this->value] ?? null;

        $before = $previous[$this->value] ?? null;

        return $now === null || $before === null ? null : round((float) $now - (float) $before, 2);
    }

    /**
     * The badge variant of a change: an improvement is a success, a decline a danger.
     */
    public function deltaVariant(float $delta): string
    {
        if ($delta === 0.0) {
            return 'neutral';
        }

        return ($delta > 0) === $this->higherIsBetter() ? 'success' : 'danger';
    }
}
