<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\MasterDataManagement\Contracts\RelationTypeTranslation as RelationTypeTranslationContract;

#[Fillable(['label'])]
#[Table(name: 'mdm_relation_type_translations')]
#[WithoutTimestamps]
class RelationTypeTranslation extends Model implements RelationTypeTranslationContract {}
