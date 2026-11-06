<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\ModelExportRequest;
use Webkul\MasterDataManagement\Http\Requests\ModelImportRequest;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleExporter;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleImporter;
use Webkul\User\Models\Admin;

class ModelTransferController extends Controller
{
    use RespondsWithRedirect;

    public const int MAX_REPORTED_ERRORS = 20;

    public function __construct(
        protected ModelBundleExporter $exporter,
        protected ModelBundleImporter $importer,
    ) {}

    /**
     * Download the model bundle of the chosen entity types, or of the whole model.
     */
    public function export(ModelExportRequest $request): StreamedResponse
    {
        $bundle = $this->exporter->export($request->typeCodes());

        return response()->streamDownload(
            function () use ($bundle): void {
                echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            },
            'masterdata-model-'.Date::now()->format('YmdHis').'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * Import an uploaded model bundle, reporting the bundle's validation errors against the file.
     *
     * @throws ValidationException
     */
    public function import(ModelImportRequest $request): JsonResponse
    {
        $admin = auth()->guard('admin')->user();

        try {
            $result = $this->importer->import($request->bundle(), $request->boolean('publish'), $admin instanceof Admin ? $admin : null);
        } catch (ValidationException $exception) {
            $messages = [];

            foreach ($exception->errors() as $path => $errors) {
                foreach ($errors as $error) {
                    $messages[] = trans('masterdata::app.model-transfer.errors.at-path', ['path' => $path, 'message' => $error]);
                }
            }

            throw ValidationException::withMessages(['file' => array_slice($messages, 0, self::MAX_REPORTED_ERRORS)]);
        }

        return $this->redirectResponse(
            route('admin.masterdata.entity_types.index'),
            trans('masterdata::app.model-transfer.import.success', $result->totals()),
        );
    }
}
