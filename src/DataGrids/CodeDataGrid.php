<?php

namespace Webkul\MasterDataManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;

class CodeDataGrid extends DataGrid
{
    protected $sortColumn = 'sort';

    protected $sortOrder = 'asc';

    protected $itemsPerPage = 50;

    /**
     * Prepare query builder.
     *
     * Each active locale's label is joined as `label_{locale}`, and the raw active flag is selected again, so the edit modal opens prefilled from the row.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mdm_codes')
            ->leftJoin('mdm_code_translations as requested_translation', function ($leftJoin): void {
                $leftJoin->on('requested_translation.code_id', '=', 'mdm_codes.id')
                    ->where('requested_translation.locale', core()->getRequestedLocaleCode());
            })
            ->where('mdm_codes.version_id', $this->versionId())
            ->select(
                'mdm_codes.id',
                'mdm_codes.code',
                'mdm_codes.parent_code',
                'mdm_codes.sort',
                'mdm_codes.is_active',
                'mdm_codes.is_active as is_active_value',
                'mdm_codes.effective_from',
                'mdm_codes.effective_to',
                'requested_translation.label as label'
            );

        foreach ($this->localeCodes() as $index => $locale) {
            $alias = 'locale_translation_'.$index;

            $queryBuilder->leftJoin('mdm_code_translations as '.$alias, function ($leftJoin) use ($alias, $locale): void {
                $leftJoin->on($alias.'.code_id', '=', 'mdm_codes.id')
                    ->where($alias.'.locale', $locale);
            })->addSelect($alias.'.label as label_'.$locale);
        }

        $this->addFilter('id', 'mdm_codes.id');
        $this->addFilter('code', 'mdm_codes.code');
        $this->addFilter('parent_code', 'mdm_codes.parent_code');
        $this->addFilter('sort', 'mdm_codes.sort');
        $this->addFilter('is_active', 'mdm_codes.is_active');
        $this->addFilter('effective_from', 'mdm_codes.effective_from');
        $this->addFilter('effective_to', 'mdm_codes.effective_to');
        $this->addFilter('label', 'requested_translation.label');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        foreach (['code' => 'code', 'label' => 'label', 'parent_code' => 'parent-code'] as $index => $key) {
            $this->addColumn([
                'index'      => $index,
                'label'      => trans('masterdata::app.codes.datagrid.'.$key),
                'type'       => 'string',
                'searchable' => true,
                'filterable' => true,
                'sortable'   => true,
            ]);
        }

        $this->addColumn([
            'index'      => 'sort',
            'label'      => trans('masterdata::app.codes.datagrid.sort'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'is_active',
            'label'      => trans('masterdata::app.codes.datagrid.is-active'),
            'type'       => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans('masterdata::app.codes.datagrid.'.($row->is_active ? 'yes' : 'no'))),
        ]);

        foreach (['effective_from' => 'effective-from', 'effective_to' => 'effective-to'] as $index => $key) {
            $this->addColumn([
                'index'      => $index,
                'label'      => trans('masterdata::app.codes.datagrid.'.$key),
                'type'       => 'date_range',
                'searchable' => false,
                'filterable' => true,
                'sortable'   => true,
            ]);
        }
    }

    /**
     * Prepare actions: only a draft version's codes can be changed.
     */
    public function prepareActions(): void
    {
        $version = resolve(CodeListVersionRepository::class)->findForCodeList((int) request()->route('id'), $this->versionId());

        if ($version?->status !== CodeListVersionStatus::Draft) {
            return;
        }

        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('masterdata::app.codes.datagrid.edit'),
            'method' => 'PUT',
            'url'    => fn ($row): string => route('admin.masterdata.codes.update', ['id' => $version->code_list_id, 'versionId' => $version->id, 'codeId' => $row->id]),
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => trans('masterdata::app.codes.datagrid.delete'),
            'method' => 'DELETE',
            'url'    => fn ($row): string => route('admin.masterdata.codes.destroy', ['id' => $version->code_list_id, 'versionId' => $version->id, 'codeId' => $row->id]),
        ]);
    }

    protected function versionId(): int
    {
        return (int) request()->route('versionId');
    }

    /**
     * The active locale codes that are safe to use in a column alias.
     *
     * @return array<int, string>
     */
    protected function localeCodes(): array
    {
        return core()->getAllActiveLocales()
            ->pluck('code')
            ->filter(fn ($code): bool => is_string($code) && preg_match('/^[A-Za-z]{2,3}(_[A-Za-z0-9]{2,8})*$/', $code) === 1)
            ->values()
            ->all();
    }
}
