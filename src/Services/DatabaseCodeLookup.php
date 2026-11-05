<?php

namespace Webkul\MasterDataManagement\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Contracts\CodeLookup;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;

class DatabaseCodeLookup implements CodeLookup
{
    public function __construct(protected CodeListVersionRepository $versions) {}

    /**
     * {@inheritdoc}
     *
     * The valid code set of a list and date is read once per request.
     */
    public function isValid(int $codeListId, string $code, ?DateTimeInterface $on = null): bool
    {
        $date = ($on ?? now())->format('Y-m-d');

        $key = 'masterdata.codes.'.$codeListId.'.'.$date;

        $memo = resolve(RequestMemo::class);

        if (! $memo->has($key)) {
            $memo->set($key, $this->codeSet($codeListId, $date));
        }

        return isset($memo->get($key)[$code]);
    }

    /**
     * @return array<string, true>
     */
    protected function codeSet(int $codeListId, string $date): array
    {
        $version = $this->versions->effectiveOn($codeListId, new DateTimeImmutable($date));

        if ($version === null) {
            return [];
        }

        $set = [];

        DB::table('mdm_codes')
            ->where('version_id', $version->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>', $date))
            ->orderBy('id')
            ->chunkById(5000, function ($rows) use (&$set): void {
                foreach ($rows as $row) {
                    $set[(string) $row->code] = true;
                }
            });

        return $set;
    }
}
