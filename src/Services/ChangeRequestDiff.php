<?php

namespace Webkul\MasterDataManagement\Services;

use Webkul\MasterDataManagement\Enums\ChangeOperation;
use Webkul\MasterDataManagement\Models\ChangeRequest;
use Webkul\MasterDataManagement\Repositories\RecordRepository;
use Webkul\User\Models\Admin;

/**
 * Lists what a held change alters: label, status, parent, each field value and each reference, before and after.
 *
 * @phpstan-type DiffRow array{label: string, before: string, after: string}
 */
class ChangeRequestDiff
{
    public function __construct(
        protected FieldRuleBuilder $fieldRuleBuilder,
        protected RecordFormBuilder $recordFormBuilder,
        protected RecordRepository $recordRepository,
    ) {}

    /**
     * The changed attributes of the request, in form order; a create lists every value it sets.
     *
     * @return list<DiffRow>
     */
    public function rows(ChangeRequest $changeRequest, ?Admin $admin = null): array
    {
        $before = (array) ($changeRequest->before ?? []);
        $after = (array) ($changeRequest->after ?? []);

        if ($changeRequest->operation->touchesXrefs()) {
            return [$this->xrefRow((array) ($before['xref'] ?? []), (array) ($after['xref'] ?? []))];
        }

        $rows = [];

        if ($changeRequest->operation === ChangeOperation::Create) {
            $rows[] = $this->row(
                trans('masterdata::app.records.fields.code'),
                null,
                $after['code'] ?? trans('masterdata::app.approvals.diff.generated-code'),
            );
        }

        $rows[] = $this->row(trans('masterdata::app.records.fields.label'), $before['label'] ?? null, $after['label'] ?? null);

        $rows[] = $this->row(
            trans('masterdata::app.records.fields.status'),
            $this->status($before['status'] ?? null),
            $this->status($after['status'] ?? null),
        );

        $rows[] = $this->parentRow($before['parent_id'] ?? null, $after['parent_id'] ?? null, $admin);

        $labels = $this->fieldRuleBuilder->fieldLabels($changeRequest->entityType);

        $old = $this->flatten((array) ($before['values'] ?? []), $labels);
        $new = $this->flatten((array) ($after['values'] ?? []), $labels);

        foreach (array_keys($old + $new) as $key) {
            $rows[] = $this->row($new[$key]['label'] ?? $old[$key]['label'], $old[$key]['value'] ?? null, $new[$key]['value'] ?? null);
        }

        array_push($rows, ...$this->referenceRows((array) ($before['references'] ?? []), (array) ($after['references'] ?? []), $labels, $admin));

        return array_values(array_filter($rows, fn (array $row): bool => $row['before'] !== $row['after']));
    }

    /**
     * Build the row of a held link or unlink: the source system's external id before and after.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return DiffRow
     */
    protected function xrefRow(array $before, array $after): array
    {
        return $this->row(
            trans('masterdata::app.approvals.diff.xref', ['system' => $after['source_system'] ?? $before['source_system'] ?? '']),
            $before['external_id'] ?? null,
            $after['external_id'] ?? null,
        );
    }

    /**
     * Build the parent row, naming each parent by its code.
     *
     * @return DiffRow
     */
    protected function parentRow(mixed $before, mixed $after, ?Admin $admin): array
    {
        $ids = array_values(array_filter([$before, $after], is_numeric(...)));

        $codes = $this->recordRepository->codesByIds(array_map(intval(...), $ids), $admin);

        $name = fn (mixed $id): string => $id === null
            ? trans('masterdata::app.hierarchy.no-parent')
            : ($codes[(int) $id] ?? trans('masterdata::app.hierarchy.hidden-parent'));

        return $this->row(trans('masterdata::app.approvals.diff.parent'), $name($before), $name($after));
    }

    /**
     * Build one row per reference field, naming each referenced record by its code.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, string>  $labels
     * @return list<DiffRow>
     */
    protected function referenceRows(array $before, array $after, array $labels, ?Admin $admin): array
    {
        $uuids = array_values(array_unique(array_map(strval(...), array_merge(...array_values(array_map(
            fn (mixed $list): array => (array) $list,
            $before + $after,
        ))))));

        $ids = $this->recordRepository->idsByUuids($uuids);

        $codes = $this->recordRepository->codesByIds(array_values(array_unique($ids)), $admin);

        $name = fn (mixed $list): ?string => implode(', ', array_map(
            fn (mixed $uuid): string => $codes[$ids[(string) $uuid] ?? 0] ?? trans('masterdata::app.approvals.diff.hidden-record'),
            (array) $list,
        )) ?: null;

        $rows = [];

        foreach (array_keys($before + $after) as $code) {
            $rows[] = $this->row($labels['values.common.'.$code] ?? (string) $code, $name($before[$code] ?? []), $name($after[$code] ?? []));
        }

        return $rows;
    }

    /**
     * Flatten a values document into display rows keyed by section, locale and field code.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $labels
     * @return array<string, array{label: string, value: string}>
     */
    protected function flatten(array $values, array $labels): array
    {
        $rows = [];

        foreach ((array) ($values['common'] ?? []) as $code => $value) {
            $rows['common.'.$code] = [
                'label' => $labels['values.common.'.$code] ?? (string) $code,
                'value' => $this->recordFormBuilder->display($value),
            ];
        }

        foreach ((array) ($values['locale_specific'] ?? []) as $locale => $localeValues) {
            foreach ((array) $localeValues as $code => $value) {
                $rows['locale.'.$locale.'.'.$code] = [
                    'label' => ($labels['values.locale_specific.*.'.$code] ?? (string) $code).' ('.$locale.')',
                    'value' => $this->recordFormBuilder->display($value),
                ];
            }
        }

        return $rows;
    }

    /**
     * Translate a stored record status.
     */
    protected function status(mixed $status): ?string
    {
        return is_string($status) && $status !== '' ? trans('masterdata::app.records.statuses.'.$status) : null;
    }

    /**
     * Build one diff row with display strings.
     *
     * @return DiffRow
     */
    protected function row(string $label, mixed $before, mixed $after): array
    {
        return [
            'label'  => $label,
            'before' => $this->recordFormBuilder->display($before),
            'after'  => $this->recordFormBuilder->display($after),
        ];
    }
}
