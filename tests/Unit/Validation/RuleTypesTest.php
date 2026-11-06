<?php

use Webkul\MasterDataManagement\Enums\ValidationRuleType;
use Webkul\MasterDataManagement\Validation\Contracts\ValueRule;
use Webkul\MasterDataManagement\Validation\RuleTypeRegistry;

it('checks a value against each built-in rule type', function (string $type, mixed $value, array $params, bool $passes, array $siblings = []) {
    expect(app(RuleTypeRegistry::class)->get($type)->passes($value, $params, $siblings))->toBe($passes);
})->with([
    'regex match'              => ['regex', 'AB-12', ['pattern' => '/^[A-Z]{2}-\d+$/'], true],
    'regex miss'               => ['regex', 'ab-12', ['pattern' => '/^[A-Z]{2}-\d+$/'], false],
    'min at bound'             => ['min', 10, ['value' => 10], true],
    'min below'                => ['min', '9.5', ['value' => 10], false],
    'min not numeric'          => ['min', 'ten', ['value' => 10], false],
    'max at bound'             => ['max', '100', ['value' => 100], true],
    'max above'                => ['max', 100.01, ['value' => 100], false],
    'length inside'            => ['length', 'Grüße', ['min' => 2, 'max' => 5], true],
    'length too short'         => ['length', 'A', ['min' => 2], false],
    'length too long'          => ['length', 'Grüßen', ['max' => 5], false],
    'length of a list'         => ['length', ['a', 'b', 'c'], ['max' => 2], false],
    'cross field greater'      => ['cross_field', 20, ['field' => 'min_qty', 'operator' => 'gt'], true, ['min_qty' => 5]],
    'cross field not greater'  => ['cross_field', 5, ['field' => 'min_qty', 'operator' => 'gt'], false, ['min_qty' => 5]],
    'cross field dates'        => ['cross_field', '2026-01-01', ['field' => 'valid_from', 'operator' => 'gte'], true, ['valid_from' => '2025-12-31']],
    'cross field equal text'   => ['cross_field', 'abc', ['field' => 'confirm', 'operator' => 'eq'], false, ['confirm' => 'abd']],
    'cross field differs'      => ['cross_field', 'abc', ['field' => 'confirm', 'operator' => 'neq'], true, ['confirm' => 'abd']],
    'cross field other empty'  => ['cross_field', 'abc', ['field' => 'confirm', 'operator' => 'eq'], true, ['confirm' => null]],
    'one of codes hit'         => ['one_of_codes', 'DE', ['codes' => ['DE', 'FR']], true],
    'one of codes miss'        => ['one_of_codes', 'US', ['codes' => ['DE', 'FR']], false],
    'one of codes list'        => ['one_of_codes', ['DE', 'US'], ['codes' => ['DE', 'FR']], false],
    'date range inside'        => ['date_range', '2026-06-01', ['after' => '2026-01-01', 'before' => '2026-12-31'], true],
    'date range before start'  => ['date_range', '2025-06-01', ['after' => '2026-01-01'], false],
    'date range after end'     => ['date_range', '2027-01-01', ['before' => '2026-12-31'], false],
    'date range not a date'    => ['date_range', 'someday', ['after' => '2026-01-01'], false],
]);

it('registers every built-in rule type', function () {
    $registry = app(RuleTypeRegistry::class);

    foreach (ValidationRuleType::cases() as $case) {
        expect($registry->get($case->value))->toBeInstanceOf(ValueRule::class);
    }

    expect($registry->codes())->toBe(array_map(fn (ValidationRuleType $case): string => $case->value, ValidationRuleType::cases()));
});

it('fills the default message placeholders from the params', function () {
    $registry = app(RuleTypeRegistry::class);

    expect($registry->get('length')->placeholders(['min' => 2, 'max' => 5]))->toBe(['min' => '2', 'max' => '5'])
        ->and($registry->get('one_of_codes')->placeholders(['codes' => ['DE', 'FR']]))->toBe(['codes' => 'DE, FR']);
});
