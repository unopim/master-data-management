<?php

namespace Webkul\MasterDataManagement\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\MasterDataManagement\Models\AccessGrant;
use Webkul\MasterDataManagement\Models\Alias;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListTranslation;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Models\CodeTranslation;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\EntityTypeTranslation;
use Webkul\MasterDataManagement\Models\EntityTypeVersion;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\FieldTransform;
use Webkul\MasterDataManagement\Models\FieldTranslation;
use Webkul\MasterDataManagement\Models\FieldValidationRule;
use Webkul\MasterDataManagement\Models\Icon;
use Webkul\MasterDataManagement\Models\IdempotencyKey;
use Webkul\MasterDataManagement\Models\MatchCandidate;
use Webkul\MasterDataManagement\Models\MatchRule;
use Webkul\MasterDataManagement\Models\QualityIssue;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\RecordMerge;
use Webkul\MasterDataManagement\Models\Relation;
use Webkul\MasterDataManagement\Models\RelationType;
use Webkul\MasterDataManagement\Models\RelationTypeTranslation;
use Webkul\MasterDataManagement\Models\RoleEntityPermission;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\SurvivorshipRule;
use Webkul\MasterDataManagement\Models\Xref;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        AccessGrant::class,
        Alias::class,
        ChangeRequest::class,
        Code::class,
        CodeList::class,
        CodeListTranslation::class,
        CodeListVersion::class,
        CodeTranslation::class,
        EntityType::class,
        EntityTypeTranslation::class,
        EntityTypeVersion::class,
        Field::class,
        FieldTransform::class,
        FieldTranslation::class,
        FieldValidationRule::class,
        Icon::class,
        IdempotencyKey::class,
        MatchCandidate::class,
        MatchRule::class,
        QualityIssue::class,
        Record::class,
        RecordMerge::class,
        Relation::class,
        RelationType::class,
        RelationTypeTranslation::class,
        RoleEntityPermission::class,
        SourceSystem::class,
        SurvivorshipRule::class,
        Xref::class,
    ];
}
