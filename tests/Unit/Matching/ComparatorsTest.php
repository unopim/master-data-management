<?php

use Webkul\MasterDataManagement\Matching\ComparatorRegistry;
use Webkul\MasterDataManagement\Matching\Comparators\DateComparator;
use Webkul\MasterDataManagement\Matching\Comparators\ExactComparator;
use Webkul\MasterDataManagement\Matching\Comparators\FuzzyComparator;
use Webkul\MasterDataManagement\Matching\Comparators\NumericComparator;
use Webkul\MasterDataManagement\Matching\Comparators\PhoneticComparator;
use Webkul\MasterDataManagement\Matching\Comparators\TokenComparator;
use Webkul\MasterDataManagement\Matching\Contracts\Comparator;

it('compares exact values after normalisation', function () {
    $exact = app(ExactComparator::class);

    expect($exact->compare('  Línka–3 ', 'linka 3', []))->toBe(1.0)
        ->and($exact->compare('PL-01', 'pl 01', []))->toBe(1.0)
        ->and($exact->compare('PL-01', 'PL-02', []))->toBe(0.0)
        ->and($exact->compare('', '', []))->toBe(0.0);
});

it('blends edit distance, common characters and word overlap in the fuzzy comparator', function () {
    $fuzzy = app(FuzzyComparator::class);

    expect($fuzzy->assess('Filler Line North', 'filler line north'))->toBe([1.0, 'exact'])
        ->and($fuzzy->assess('PL 01', 'PL01'))->toBe([0.97, 'compact'])
        ->and($fuzzy->assess('', 'anything'))->toBe([0.0, 'similar']);

    [$score, $how] = $fuzzy->assess('Filler Line North', 'Filler Line Nort');

    expect($how)->toBe('similar')
        ->and($score)->toBeGreaterThan(0.7)->toBeLessThanOrEqual(0.95)
        ->and($fuzzy->compare('Filler Line North', 'Filler Line Nort', []))->toBe($score);
});

it('scores the share of shared words in the token comparator', function () {
    $token = app(TokenComparator::class);

    expect($token->compare('North Filler Line', 'line filler north', []))->toBe(1.0)
        ->and($token->compare('Filler Line North', 'Filler Line South', []))->toBe(0.5)
        ->and($token->compare('alpha', 'beta', []))->toBe(0.0);
});

it('matches words that sound alike in the phonetic comparator', function () {
    $phonetic = app(PhoneticComparator::class);

    expect($phonetic->compare('Smith John', 'Smyth Jon', []))->toBe(1.0)
        ->and($phonetic->compare('Smith', 'Jones', []))->toBe(0.0);
});

it('scores numbers within the tolerance and only equal numbers without one', function () {
    $numeric = app(NumericComparator::class);

    expect($numeric->compare('100', '100.0', []))->toBe(1.0)
        ->and($numeric->compare('100', '101', []))->toBe(0.0)
        ->and($numeric->compare('100', '102', ['tolerance' => 10]))->toBe(0.8)
        ->and($numeric->compare('100', '120', ['tolerance' => 10]))->toBe(0.0)
        ->and($numeric->compare('abc', '100', ['tolerance' => 10]))->toBe(0.0);
});

it('scores dates within the day window and only the same day without one', function () {
    $date = app(DateComparator::class);

    expect($date->compare('2026-01-10', '2026-01-10 15:30:00', []))->toBe(1.0)
        ->and($date->compare('2026-01-10', '2026-01-11', []))->toBe(0.0)
        ->and($date->compare('2026-01-10', '2026-01-12', ['days' => 10]))->toBe(0.8)
        ->and($date->compare('2026-01-10', '2026-03-10', ['days' => 10]))->toBe(0.0)
        ->and($date->compare('not a date', '2026-01-10', ['days' => 10]))->toBe(0.0);
});

it('resolves the configured comparators and accepts extensions through the config', function () {
    $registry = app(ComparatorRegistry::class);

    expect($registry->codes())->toBe(['exact', 'fuzzy', 'token', 'phonetic', 'numeric', 'date'])
        ->and($registry->get('fuzzy'))->toBeInstanceOf(FuzzyComparator::class)
        ->and($registry->has('custom'))->toBeFalse();

    $custom = new class implements Comparator
    {
        public function compare(string $left, string $right, array $options): float
        {
            return 0.5;
        }
    };

    app()->instance('test.comparator', $custom);

    config()->set('masterdata.matching.comparators.custom', 'test.comparator');

    expect($registry->get('custom')->compare('a', 'b', []))->toBe(0.5)
        ->and($registry->label('custom'))->toBe('custom')
        ->and($registry->label('exact'))->toBe(trans('masterdata::app.match-rules.comparators.exact'));

    expect(fn () => $registry->get('missing'))->toThrow(InvalidArgumentException::class);
});
