<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\MasterDataManagement\Contracts\HierarchyTranslation as HierarchyTranslationContract;

#[Fillable(['label'])]
#[Table(name: 'mdm_hierarchy_translations')]
#[WithoutTimestamps]
class HierarchyTranslation extends Model implements HierarchyTranslationContract {}
