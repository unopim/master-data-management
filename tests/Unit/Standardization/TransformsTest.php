<?php

use Webkul\MasterDataManagement\Enums\FieldTransformType;
use Webkul\MasterDataManagement\Standardization\Contracts\ValueTransform;
use Webkul\MasterDataManagement\Standardization\TransformRegistry;

it('applies each built-in transform', function (string $code, string $input, string $expected) {
    expect(app(TransformRegistry::class)->get($code)->apply($input))->toBe($expected);
})->with([
    'trim'                  => ['trim', "  Acme Ltd \t", 'Acme Ltd'],
    'lower'                 => ['lower', 'ÉCOLE Acme', 'école acme'],
    'upper'                 => ['upper', 'straße ab-1', 'STRASSE AB-1'],
    'title'                 => ['title', 'jean-luc picard', 'Jean-Luc Picard'],
    'collapse spaces'       => ['collapse_spaces', "Acme   \t Holdings\n Ltd", 'Acme Holdings Ltd'],
    'email normalize'       => ['email_normalize', '  John.Doe@Example.COM ', 'john.doe@example.com'],
    'phone with plus'       => ['phone_e164', ' +49 (30) 1234-5678 ', '+493012345678'],
    'phone with 00 prefix'  => ['phone_e164', '0049 30 1234 5678', '+493012345678'],
    'phone without country' => ['phone_e164', '(030) 1234-5678', '03012345678'],
    'phone without digits'  => ['phone_e164', 'n/a', ''],
    'strip non alnum'       => ['strip_non_alnum', 'AB-12 / ç#3', 'AB12ç3'],
]);

it('registers every built-in transform type', function () {
    $registry = app(TransformRegistry::class);

    foreach (FieldTransformType::cases() as $case) {
        expect($registry->has($case->value))->toBeTrue()
            ->and($registry->get($case->value))->toBeInstanceOf(ValueTransform::class);
    }

    expect($registry->codes())->toBe(array_map(fn (FieldTransformType $case): string => $case->value, FieldTransformType::cases()));
});

it('resolves a transform a package adds through the config', function () {
    config(['masterdata.standardization.transforms.reverse' => ReverseTransform::class]);

    $registry = app(TransformRegistry::class);

    expect($registry->has('reverse'))->toBeTrue()
        ->and($registry->get('reverse')->apply('abc'))->toBe('cba')
        ->and($registry->label('reverse'))->toBe('reverse');
});

it('refuses an unknown transform', function () {
    app(TransformRegistry::class)->get('telepathy');
})->throws(InvalidArgumentException::class);

class ReverseTransform implements ValueTransform
{
    public function apply(string $value): string
    {
        return strrev($value);
    }
}
