<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\MasterDataManagement\Contracts\Icon as IconContract;

#[Fillable([
    'code',
    'name',
    'path',
])]
#[Table(name: 'mdm_icons')]
class Icon extends Model implements HistoryAuditable, IconContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['masterdata_icon'];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Get the admin URL that serves the icon file; the version query busts the browser cache on replace.
     */
    public function url(): string
    {
        return static::urlFor((int) $this->id, (string) $this->path);
    }

    /**
     * Get the file URL from raw row values, for listings that read icons through a join.
     */
    public static function urlFor(int $id, string $path): string
    {
        return route('admin.masterdata.icons.file', ['id' => $id, 'v' => substr(md5($path), 0, 8)]);
    }

    /**
     * Whether the icon is an SVG, drawn as a theme coloured mask; rasters keep their own colours.
     */
    public function isVector(): bool
    {
        return static::isVectorPath((string) $this->path);
    }

    /**
     * Whether a stored icon path is an SVG.
     */
    public static function isVectorPath(string $path): bool
    {
        return str_ends_with(strtolower($path), '.svg');
    }

    /**
     * Get the entity types that show the icon.
     */
    public function entityTypes(): HasMany
    {
        return $this->hasMany(EntityTypeProxy::modelClass());
    }
}
