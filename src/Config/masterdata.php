<?php

use Webkul\MasterDataManagement\Enums\FieldTransformType;
use Webkul\MasterDataManagement\Enums\ValidationRuleType;
use Webkul\MasterDataManagement\FieldTypes\BooleanType;
use Webkul\MasterDataManagement\FieldTypes\DateTimeType;
use Webkul\MasterDataManagement\FieldTypes\DateType;
use Webkul\MasterDataManagement\FieldTypes\DecimalType;
use Webkul\MasterDataManagement\FieldTypes\EmailType;
use Webkul\MasterDataManagement\FieldTypes\FileType;
use Webkul\MasterDataManagement\FieldTypes\ImageType;
use Webkul\MasterDataManagement\FieldTypes\IntegerType;
use Webkul\MasterDataManagement\FieldTypes\MultiselectType;
use Webkul\MasterDataManagement\FieldTypes\ReferenceType;
use Webkul\MasterDataManagement\FieldTypes\SelectType;
use Webkul\MasterDataManagement\FieldTypes\TextareaType;
use Webkul\MasterDataManagement\FieldTypes\TextType;
use Webkul\MasterDataManagement\FieldTypes\UrlType;
use Webkul\MasterDataManagement\Matching\Comparators\DateComparator;
use Webkul\MasterDataManagement\Matching\Comparators\ExactComparator;
use Webkul\MasterDataManagement\Matching\Comparators\FuzzyComparator;
use Webkul\MasterDataManagement\Matching\Comparators\NumericComparator;
use Webkul\MasterDataManagement\Matching\Comparators\PhoneticComparator;
use Webkul\MasterDataManagement\Matching\Comparators\TokenComparator;
use Webkul\MasterDataManagement\Quality\Rules\DuplicateRule;
use Webkul\MasterDataManagement\Quality\Rules\FieldRuleWarningRule;
use Webkul\MasterDataManagement\Quality\Rules\HierarchyRule;
use Webkul\MasterDataManagement\Quality\Rules\RequiredXrefRule;
use Webkul\MasterDataManagement\Quality\Rules\ValidityRule;
use Webkul\MasterDataManagement\Services\NativeChangeGate;
use Webkul\MasterDataManagement\Standardization\Transforms\CollapseSpacesTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\EmailNormalizeTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\LowerTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\PhoneE164Transform;
use Webkul\MasterDataManagement\Standardization\Transforms\StripNonAlnumTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\TitleTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\TrimTransform;
use Webkul\MasterDataManagement\Standardization\Transforms\UpperTransform;
use Webkul\MasterDataManagement\Survivorship\Strategies\KeepTargetStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\ManualStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\MostCompleteStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\MostRecentStrategy;
use Webkul\MasterDataManagement\Survivorship\Strategies\SourcePriorityStrategy;
use Webkul\MasterDataManagement\Validation\Rules\CrossFieldRule;
use Webkul\MasterDataManagement\Validation\Rules\DateRangeRule;
use Webkul\MasterDataManagement\Validation\Rules\LengthRule;
use Webkul\MasterDataManagement\Validation\Rules\MaxRule;
use Webkul\MasterDataManagement\Validation\Rules\MinRule;
use Webkul\MasterDataManagement\Validation\Rules\OneOfCodesRule;
use Webkul\MasterDataManagement\Validation\Rules\RegexRule;

/**
 * `export_disk` names the filesystem disk the `masterdata_records` exporter publishes to, under
 * `{export_path}/{entity_type}/{Y/m/d}/`; ADLS Gen2 and Microsoft Fabric (OneLake shortcut) consumers read the JSONL
 * files from there. The `azure` disk in config/filesystems.php (azure-oss/storage-blob-laravel) writes to Azure Blob
 * Storage: set MASTERDATA_EXPORT_DISK=azure with AZURE_STORAGE_CONNECTION_STRING, AZURE_STORAGE_CONTAINER and
 * optionally AZURE_STORAGE_PREFIX. Any other configured disk, such as `s3`, works the same way.
 *
 * `validation.rule_types` and `standardization.transforms` map codes to the classes behind field validation rules and
 * field transforms; a package registers its own by adding entries.
 *
 * `scheduled_imports` feeds the `masterdata:import {job_code}` command: an integration tool drops files into
 * `{path}/{job_code}/` on `disk`, the command imports the oldest one as `user` (an admin e-mail) and moves it to
 * `processed/`. `jobs` maps job codes to cron expressions the scheduler runs the command on.
 */
return [
    'batch_size' => 500,

    'change_gate' => NativeChangeGate::class,

    'export_disk' => env('MASTERDATA_EXPORT_DISK', 'local'),

    'export_path' => env('MASTERDATA_EXPORT_PATH', 'masterdata/exports'),

    'scheduled_imports' => [
        'disk' => env('MASTERDATA_IMPORT_DISK', 'local'),
        'path' => env('MASTERDATA_IMPORT_PATH', 'masterdata/imports'),
        'user' => env('MASTERDATA_IMPORT_USER'),
        'jobs' => [],
    ],

    'default_locale' => env('APP_LOCALE', 'en_US'),

    'entity_type_icons' => [
        'icon-dashboard',
        'icon-catalog',
        'icon-product',
        'icon-attribute',
        'icon-channel',
        'icon-folder',
        'icon-file',
        'icon-configuration',
        'icon-setting',
        'icon-processing',
        'icon-data-transfer',
        'icon-language',
        'icon-calendar',
        'icon-image',
        'icon-star',
        'icon-notification',
        'icon-information',
        'icon-magic-ai',
    ],

    'match' => [
        'block_cap' => 200,
    ],

    'matching' => [
        'comparators' => [
            'exact'    => ExactComparator::class,
            'fuzzy'    => FuzzyComparator::class,
            'token'    => TokenComparator::class,
            'phonetic' => PhoneticComparator::class,
            'numeric'  => NumericComparator::class,
            'date'     => DateComparator::class,
        ],
    ],

    'merge' => [
        'max_children' => 500,
    ],

    'survivorship' => [
        'strategies' => [
            'source_priority' => SourcePriorityStrategy::class,
            'most_recent'     => MostRecentStrategy::class,
            'most_complete'   => MostCompleteStrategy::class,
            'keep_target'     => KeepTargetStrategy::class,
            'manual'          => ManualStrategy::class,
        ],
    ],

    'quality' => [
        'duplicate_threshold' => 0.85,

        'rules' => [
            ValidityRule::class,
            HierarchyRule::class,
            RequiredXrefRule::class,
            DuplicateRule::class,
            FieldRuleWarningRule::class,
        ],
    ],

    'validation' => [
        'rule_types' => [
            ValidationRuleType::Regex->value      => RegexRule::class,
            ValidationRuleType::Min->value        => MinRule::class,
            ValidationRuleType::Max->value        => MaxRule::class,
            ValidationRuleType::Length->value     => LengthRule::class,
            ValidationRuleType::CrossField->value => CrossFieldRule::class,
            ValidationRuleType::OneOfCodes->value => OneOfCodesRule::class,
            ValidationRuleType::DateRange->value  => DateRangeRule::class,
        ],
    ],

    'standardization' => [
        'transforms' => [
            FieldTransformType::Trim->value           => TrimTransform::class,
            FieldTransformType::Lower->value          => LowerTransform::class,
            FieldTransformType::Upper->value          => UpperTransform::class,
            FieldTransformType::Title->value          => TitleTransform::class,
            FieldTransformType::CollapseSpaces->value => CollapseSpacesTransform::class,
            FieldTransformType::EmailNormalize->value => EmailNormalizeTransform::class,
            FieldTransformType::PhoneE164->value      => PhoneE164Transform::class,
            FieldTransformType::StripNonAlnum->value  => StripNonAlnumTransform::class,
        ],
    ],

    'field_types' => [
        'text'        => TextType::class,
        'textarea'    => TextareaType::class,
        'integer'     => IntegerType::class,
        'decimal'     => DecimalType::class,
        'boolean'     => BooleanType::class,
        'date'        => DateType::class,
        'datetime'    => DateTimeType::class,
        'email'       => EmailType::class,
        'url'         => UrlType::class,
        'select'      => SelectType::class,
        'multiselect' => MultiselectType::class,
        'reference'   => ReferenceType::class,
        'file'        => FileType::class,
        'image'       => ImageType::class,
    ],
];
