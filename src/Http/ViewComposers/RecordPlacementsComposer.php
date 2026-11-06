<?php

namespace Webkul\MasterDataManagement\Http\ViewComposers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Webkul\MasterDataManagement\Enums\RecordStatus;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Services\HierarchyPresenter;

/**
 * Feeds the record page's alternate hierarchies panel with the record's placements and whether the admin may change them.
 */
class RecordPlacementsComposer
{
    public function __construct(protected HierarchyPresenter $hierarchyPresenter) {}

    /**
     * Bind the panel data to the view.
     */
    public function compose(View $view): void
    {
        $record = $view->getData()['record'] ?? null;

        if (! $record instanceof Record) {
            $view->with(['placements' => [], 'canPlace' => false]);

            return;
        }

        $view->with([
            'placements' => $this->hierarchyPresenter->placements($record),
            'canPlace'   => in_array($record->status, [RecordStatus::Draft, RecordStatus::Active], true)
                && bouncer()->hasPermission('masterdata.records.edit')
                && Gate::allows('update', $record),
        ]);
    }
}
