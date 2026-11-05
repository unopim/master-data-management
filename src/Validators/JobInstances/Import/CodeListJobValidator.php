<?php

namespace Webkul\MasterDataManagement\Validators\JobInstances\Import;

use Illuminate\Validation\ValidationException;
use Webkul\MasterDataManagement\Importers\CodeList\Importer;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\User\Models\Admin;

class CodeListJobValidator extends ImportJobValidator
{
    public function __construct(protected CodeListRepository $codeListRepository) {}

    /**
     * Validate the settings, then that the code list exists and the admin may edit code lists.
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): void
    {
        parent::validate($data, $options);

        $codeList = $this->codeListRepository->findOneByField('code', $this->filterValue($data, Importer::FILTER_CODE_LIST));

        $admin = $this->admin();

        if (
            ! $codeList instanceof CodeList
            || ($admin instanceof Admin && $admin->role?->permission_type !== 'all' && ! $admin->hasPermission('masterdata.code_lists.edit'))
        ) {
            $this->fail(Importer::FILTER_CODE_LIST, trans('masterdata::app.imports.errors.code-list-invalid'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRules(): array
    {
        return [
            Importer::FILTER_CODE_LIST => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function filterNames(): array
    {
        return [
            Importer::FILTER_CODE_LIST => trans('masterdata::app.imports.filters.code-list'),
        ];
    }
}
