<?php

namespace Webkul\MasterDataManagement\Quality;

/**
 * How much of a record's counted fields hold a value.
 */
final readonly class Completeness
{
    /**
     * The lowest score of the complete band.
     */
    public const int COMPLETE = 100;

    /**
     * The lowest score of the partial band; anything below is low.
     */
    public const int PARTIAL = 50;

    public const string BAND_COMPLETE = 'complete';

    public const string BAND_PARTIAL = 'partial';

    public const string BAND_LOW = 'low';

    public const string BAND_NONE = 'none';

    /**
     * Every band, best first.
     */
    public const array BANDS = [self::BAND_COMPLETE, self::BAND_PARTIAL, self::BAND_LOW, self::BAND_NONE];

    /**
     * @param  ?int  $score  The filled share from 0 to 100, or null when the type counts no fields.
     * @param  list<string>  $missing  The labels of the counted fields without a value, in display order.
     */
    public function __construct(
        public ?int $score,
        public int $filled,
        public int $total,
        public array $missing = [],
    ) {}

    /**
     * The band a score falls in; a null score is unscored.
     */
    public static function band(int|float|null $score): string
    {
        return match (true) {
            $score === null           => self::BAND_NONE,
            $score >= self::COMPLETE  => self::BAND_COMPLETE,
            $score >= self::PARTIAL   => self::BAND_PARTIAL,
            default                   => self::BAND_LOW,
        };
    }

    /**
     * The `x-admin::badge` variant that colours a score.
     */
    public static function variant(int|float $score): string
    {
        return match (self::band($score)) {
            self::BAND_COMPLETE => 'success',
            self::BAND_PARTIAL  => 'warning',
            default             => 'danger',
        };
    }

    /**
     * An SQL CASE expression giving the band of a completeness column, which the caller passes already wrapped.
     */
    public static function bandCase(string $column): string
    {
        return sprintf(
            "(case when %1\$s is null then '%2\$s' when %1\$s >= %3\$d then '%4\$s' when %1\$s >= %5\$d then '%6\$s' else '%7\$s' end)",
            $column,
            self::BAND_NONE,
            self::COMPLETE,
            self::BAND_COMPLETE,
            self::PARTIAL,
            self::BAND_PARTIAL,
            self::BAND_LOW,
        );
    }
}
