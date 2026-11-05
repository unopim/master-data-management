<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Admin;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\MasterDataManagement\DataGrids\AliasDataGrid;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Http\Controllers\Concerns\RespondsWithRedirect;
use Webkul\MasterDataManagement\Http\Requests\AliasRequest;
use Webkul\MasterDataManagement\Http\Requests\AliasResolveRequest;
use Webkul\MasterDataManagement\Repositories\AliasRepository;
use Webkul\MasterDataManagement\Repositories\FieldRepository;
use Webkul\MasterDataManagement\Services\AliasResolver;

class AliasController extends Controller
{
    use RespondsWithRedirect;

    public function __construct(
        protected AliasRepository $aliasRepository,
        protected FieldRepository $fieldRepository,
        protected LocaleRepository $localeRepository,
        protected AliasResolver $aliasResolver,
    ) {}

    /**
     * Display the alias listing and, once the resolve form is submitted, what the term resolves to.
     */
    public function index(AliasResolveRequest $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return resolve(AliasDataGrid::class)->toJson();
        }

        $resolution = null;

        if ($request->isResolve()) {
            $sourceSystemId = $request->validated('resolve_source_system_id');

            $resolution = [
                'term'   => (string) $request->validated('resolve_term'),
                'target' => $this->aliasResolver->resolve(
                    (string) $request->validated('resolve_term'),
                    AliasScope::CodeList,
                    (int) $request->validated('resolve_code_list_id'),
                    $sourceSystemId === null ? null : (int) $sourceSystemId,
                ),
            ];
        }

        return view('masterdata::aliases.index', [
            'resolveInput'     => $request->isResolve() ? $request->validated() : [],
            'resolution'       => $resolution,
            'scopeTypeOptions' => array_map(
                fn (AliasScope $scope): array => ['id' => $scope->value, 'label' => trans('masterdata::app.aliases.scopes.'.$scope->value)],
                AliasScope::cases()
            ),
            'fieldOptions'  => $this->fieldRepository->scopeOptions(),
            'localeOptions' => $this->localeRepository->getActiveLocales()
                ->map(fn ($locale): array => ['id' => $locale->code, 'label' => $locale->name])
                ->values(),
        ]);
    }

    /**
     * Store a new alias.
     */
    public function store(AliasRequest $request): JsonResponse
    {
        try {
            $this->aliasRepository->create($request->validated());
        } catch (UniqueConstraintViolationException) {
            return $this->duplicate();
        }

        return $this->respond(trans('masterdata::app.aliases.create-success'));
    }

    /**
     * Update an alias.
     */
    public function update(AliasRequest $request, int $id): JsonResponse
    {
        $this->aliasRepository->findOrFail($id);

        try {
            $this->aliasRepository->update($request->validated(), $id);
        } catch (UniqueConstraintViolationException) {
            return $this->duplicate();
        }

        return $this->respond(trans('masterdata::app.aliases.update-success'));
    }

    /**
     * Delete an alias.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('masterdata.code_lists.edit'), 403);

        $this->aliasRepository->findOrFail($id);

        $this->aliasRepository->delete($id);

        return new JsonResponse([
            'message' => trans('masterdata::app.aliases.delete-success'),
        ]);
    }

    /**
     * Flash the message and answer with it and the listing to return to.
     */
    protected function respond(string $message): JsonResponse
    {
        return $this->redirectResponse(route('admin.masterdata.aliases.index'), $message);
    }

    /**
     * Answer a unique index violation the way the form request reports a duplicate.
     */
    protected function duplicate(): JsonResponse
    {
        $message = trans('masterdata::app.aliases.validation.duplicate');

        return new JsonResponse([
            'message' => $message,
            'errors'  => ['term' => [$message]],
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
