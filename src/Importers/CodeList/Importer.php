<?php

namespace Webkul\MasterDataManagement\Importers\CodeList;

use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\MasterDataManagement\Importers\MasterDataImporter;
use Webkul\MasterDataManagement\Models\Code;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;
use Webkul\User\Repositories\AdminRepository;

/**
 * Upserts the codes of one code list into its draft version, opening the next draft from the latest version when
 * the list has none. Publishing the draft stays a separate admin action. Columns: `code`, `parent_code`, `sort` and
 * `label-locale` per active locale; a new code needs a label in the default locale.
 */
class Importer extends MasterDataImporter
{
    public const string FILTER_CODE_LIST = 'code_list';

    protected const string CODE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_.\-]*$/';

    protected array $permanentAttributes = ['code'];

    protected ?CodeList $codeList = null;

    /**
     * @var list<string>
     */
    protected array $locales = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AdminRepository $adminRepository,
        protected CodeListRepository $codeListRepository,
        protected CodeRepository $codeRepository,
    ) {
        parent::__construct($importBatchRepository, $adminRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(): void
    {
        if ($this->codeList instanceof CodeList) {
            return;
        }

        $code = $this->filter(self::FILTER_CODE_LIST);

        $codeList = $code === '' ? null : $this->codeListRepository->findOneByField('code', $code);

        if (! $codeList instanceof CodeList) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.code-list-missing', ['code' => $code]));
        }

        $author = $this->author();

        if ($author->role?->permission_type !== 'all' && ! $author->hasPermission('masterdata.code_lists.edit')) {
            throw new RuntimeException(trans('masterdata::app.imports.errors.code-list-not-allowed', ['code' => $codeList->code]));
        }

        $this->codeList = $codeList;

        $this->locales = core()->getAllActiveLocales()->pluck('code')->map(strval(...))->all();
    }

    /**
     * {@inheritdoc}
     */
    protected function columnNames(): array
    {
        return ['code', 'parent_code', 'sort', ...array_map(fn (string $locale): string => 'label-'.$locale, $this->locales)];
    }

    /**
     * {@inheritdoc}
     */
    protected function checkRow(array $rowData, int $rowNumber): void
    {
        $rules = [
            'code'        => ['required', 'string', 'max:128', 'regex:'.self::CODE_PATTERN],
            'parent_code' => ['nullable', 'string', 'max:128', 'regex:'.self::CODE_PATTERN, 'different:code'],
            'sort'        => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ];

        $attributes = [
            'code'        => trans('masterdata::app.codes.form.code'),
            'parent_code' => trans('masterdata::app.codes.form.parent-code'),
            'sort'        => trans('masterdata::app.codes.form.sort'),
        ];

        foreach ($this->locales as $locale) {
            $rules['label-'.$locale] = ['nullable', 'string', 'max:255'];

            $attributes['label-'.$locale] = trans('masterdata::app.codes.form.label');
        }

        $validator = Validator::make(array_map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $rowData), $rules, [], $attributes);

        $this->invalidFromErrors($rowNumber, $validator->errors()->toArray());
    }

    /**
     * Write the batch into the list's draft, reading the codes it touches in one query.
     */
    protected function importRows(array $rows): void
    {
        $draft = $this->codeListRepository->draftFor($this->codeList);

        $values = array_map($this->values(...), $rows);

        $codes = $this->codeRepository->findManyForVersion((int) $draft->id, array_values(array_filter(
            array_merge(array_column($values, 'code'), array_column($values, 'parent_code')),
            is_string(...),
        )))->all();

        foreach ($rows as $index => $row) {
            $rowNumber = $this->rowNumberOf($row);

            $data = $values[$index];

            if (isset($data['parent_code']) && ! isset($codes[$data['parent_code']])) {
                $this->skipped($rowNumber, trans('masterdata::app.imports.errors.parent-code-not-found', ['code' => $data['parent_code']]));

                continue;
            }

            $existing = $codes[$data['code']] ?? null;

            if ($existing instanceof Code) {
                $this->updateCode($existing, $data);

                continue;
            }

            $created = $this->createCode($rowNumber, $draft, $data);

            if ($created instanceof Code) {
                $codes[$data['code']] = $created;
            }
        }

        $this->jobLogger?->info(trans('masterdata::app.imports.messages.code-list-draft', [
            'code'    => $this->codeList->code,
            'version' => $draft->version,
        ]));
    }

    /**
     * Create a code in the draft; a new code needs a label in the default locale.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createCode(int $rowNumber, CodeListVersion $draft, array $data): ?Code
    {
        $defaultLocale = (string) config('masterdata.default_locale', 'en_US');

        if (! isset($data['labels'][$defaultLocale])) {
            $this->skipped($rowNumber, trans('masterdata::app.imports.errors.default-label-required', ['locale' => $defaultLocale]));

            return null;
        }

        $attributes = [
            'version_id'  => (int) $draft->id,
            'code'        => $data['code'],
            'parent_code' => $data['parent_code'] ?? null,
        ];

        if (isset($data['sort'])) {
            $attributes['sort'] = (int) $data['sort'];
        }

        $code = $this->codeRepository->create($attributes + $this->translations($data['labels']));

        $this->createdItemsCount++;

        return $code;
    }

    /**
     * Update the code's parent, sort and labels the row gives, when any of them differs.
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateCode(Code $code, array $data): void
    {
        $attributes = [];

        if (isset($data['parent_code']) && $data['parent_code'] !== $code->parent_code) {
            $attributes['parent_code'] = $data['parent_code'];
        }

        if (isset($data['sort']) && (int) $data['sort'] !== (int) $code->sort) {
            $attributes['sort'] = (int) $data['sort'];
        }

        $labels = array_filter(
            $data['labels'],
            fn (string $label, string $locale): bool => $code->translations->firstWhere('locale', $locale)?->label !== $label,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($attributes === [] && $labels === []) {
            return;
        }

        $this->codeRepository->update($attributes + $this->translations($labels), $code->id);

        $this->updatedItemsCount++;
    }

    /**
     * Labels keyed by locale in the repository's translation form.
     *
     * @param  array<string, string>  $labels
     * @return array<string, array{label: string}>
     */
    protected function translations(array $labels): array
    {
        return array_map(fn (string $label): array => ['label' => $label], $labels);
    }

    /**
     * The trimmed, non-empty values of a row: code, parent code, sort and labels by locale.
     *
     * @param  array<string, mixed>  $row
     * @return array{code: string, parent_code?: string, sort?: string, labels: array<string, string>}
     */
    protected function values(array $row): array
    {
        $values = ['code' => trim((string) ($row['code'] ?? '')), 'labels' => []];

        foreach (['parent_code', 'sort'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));

            if ($value !== '') {
                $values[$key] = $value;
            }
        }

        foreach ($this->locales as $locale) {
            $label = trim((string) ($row['label-'.$locale] ?? ''));

            if ($label !== '') {
                $values['labels'][$locale] = $label;
            }
        }

        return $values;
    }
}
