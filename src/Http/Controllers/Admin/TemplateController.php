<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\TemplateInstallRequest;
use Webkul\MasterDataManagement\Services\TemplateInstaller;

class TemplateController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(protected TemplateInstaller $templateInstaller) {}

    /**
     * Install the chosen domain template, and its sample records when asked, then reopen the entity type listing.
     */
    public function store(TemplateInstallRequest $request): JsonResponse
    {
        $name = (string) $request->validated('template');

        $totals = $this->templateInstaller->install($name, $request->boolean('with_samples'));

        return $this->redirectResponse(route('admin.masterdata.entity_types.index'), trans('masterdata::app.templates.installed', [
            'name'    => $name,
            'types'   => $totals['entity_types'],
            'fields'  => $totals['fields'],
            'lists'   => $totals['code_lists'],
            'records' => $totals['records'],
        ]));
    }
}
