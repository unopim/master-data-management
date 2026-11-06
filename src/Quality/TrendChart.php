<?php

namespace Webkul\MasterDataManagement\Quality;

use Webkul\MasterDataManagement\Enums\QualityMetric;

/**
 * Plots the percentage metrics of a type's snapshots, oldest first, as polylines in a fixed SVG view box with a
 * 0 to 100 vertical scale. Days without a value leave a gap in their line.
 */
final readonly class TrendChart
{
    public const int WIDTH = 640;

    public const int HEIGHT = 220;

    public const int PAD_LEFT = 40;

    public const int PAD_RIGHT = 16;

    public const int PAD_Y = 16;

    /**
     * The horizontal gridlines, in percent.
     */
    public const array GRID = [0, 25, 50, 75, 100];

    /**
     * @param  list<array<string, mixed>>  $snapshots
     */
    public function __construct(public array $snapshots) {}

    /**
     * Whether there is anything to plot.
     */
    public function isEmpty(): bool
    {
        return $this->snapshots === [];
    }

    /**
     * Each metric's line segments as SVG point lists, its points and its latest value.
     *
     * @return list<array{metric: QualityMetric, segments: list<string>, points: list<array{x: float, y: float, value: float, date: string}>, latest: float|null}>
     */
    public function series(): array
    {
        return array_map(function (QualityMetric $metric): array {
            $segments = [];

            $current = [];

            $points = [];

            $latest = null;

            foreach ($this->snapshots as $index => $snapshot) {
                $value = $snapshot[$metric->value] ?? null;

                if ($value === null) {
                    if ($current !== []) {
                        $segments[] = implode(' ', $current);
                    }

                    $current = [];

                    continue;
                }

                $point = ['x' => $this->x($index), 'y' => $this->y((float) $value), 'value' => (float) $value, 'date' => (string) $snapshot['snapshot_date']];

                $points[] = $point;

                $current[] = $point['x'].','.$point['y'];

                $latest = (float) $value;
            }

            if ($current !== []) {
                $segments[] = implode(' ', $current);
            }

            return ['metric' => $metric, 'segments' => $segments, 'points' => $points, 'latest' => $latest];
        }, QualityMetric::cases());
    }

    /**
     * The vertical position of a percentage.
     */
    public function y(float $value): float
    {
        $height = self::HEIGHT - 2 * self::PAD_Y;

        return round(self::PAD_Y + $height * (1 - max(0.0, min(100.0, $value)) / 100), 2);
    }

    /**
     * The first and last day plotted.
     *
     * @return array{0: string, 1: string}|null
     */
    public function span(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }

        return [(string) $this->snapshots[0]['snapshot_date'], (string) $this->snapshots[array_key_last($this->snapshots)]['snapshot_date']];
    }

    /**
     * The horizontal position of the snapshot at the index; a single snapshot sits in the middle.
     */
    private function x(int $index): float
    {
        $width = self::WIDTH - self::PAD_LEFT - self::PAD_RIGHT;

        $count = count($this->snapshots);

        if ($count === 1) {
            return round(self::PAD_LEFT + $width / 2, 2);
        }

        return round(self::PAD_LEFT + $width * $index / ($count - 1), 2);
    }
}
