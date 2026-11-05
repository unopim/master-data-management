<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Core\RequestMemo;
use Webkul\MasterDataManagement\Enums\CodeListVersionStatus;
use Webkul\MasterDataManagement\Events\CodeListPublished;
use Webkul\MasterDataManagement\Exceptions\CodeListVersionNotDraftException;
use Webkul\MasterDataManagement\Models\CodeListVersion;
use Webkul\MasterDataManagement\Repositories\CodeListVersionRepository;
use Webkul\User\Models\Admin;

class CodeListPublisher
{
    public function __construct(protected CodeListVersionRepository $versions) {}

    /**
     * Publish a draft version and close the window of the version it supersedes.
     *
     * Published versions starting on or after the new effective date can never take effect and are
     * retired. The superseded version is retired only once the new one is already in effect; for a
     * future-dated publish it stays published until the new effective date.
     *
     * @throws CodeListVersionNotDraftException
     */
    public function publish(CodeListVersion $version, ?Admin $by = null): CodeListVersion
    {
        $published = DB::transaction(function () use ($version, $by): CodeListVersion {
            $siblings = $this->versions->getModel()->newQuery()
                ->where('code_list_id', $version->code_list_id)
                ->lockForUpdate()
                ->get();

            $locked = $siblings->firstWhere('id', $version->id);

            if ($locked === null || $locked->status !== CodeListVersionStatus::Draft) {
                throw new CodeListVersionNotDraftException($version);
            }

            $locked->effective_from ??= $version->effective_from;

            $today = now()->toDateString();
            $from = $locked->effective_from?->toDateString() ?? $today;

            $others = $siblings->where('id', '!=', $locked->id)
                ->filter(fn (CodeListVersion $other): bool => $other->status === CodeListVersionStatus::Published);

            foreach ($others->filter(fn (CodeListVersion $other): bool => $other->effective_from !== null && $other->effective_from->toDateString() >= $from) as $never) {
                $never->effective_to = $never->effective_from;
                $never->status = CodeListVersionStatus::Retired;
                $never->save();
            }

            $previous = $others
                ->filter(fn (CodeListVersion $other): bool => $other->status === CodeListVersionStatus::Published
                    && ($other->effective_from === null || $other->effective_from->toDateString() < $from)
                    && ($other->effective_to === null || $other->effective_to->toDateString() > $from))
                ->sortByDesc('version')
                ->first();

            if ($previous !== null) {
                $previous->effective_to = $from;
                $previous->status = $from <= $today ? CodeListVersionStatus::Retired : CodeListVersionStatus::Published;
                $previous->save();
            }

            $locked->status = CodeListVersionStatus::Published;
            $locked->effective_from = $from;
            $locked->published_by = $by?->id;
            $locked->published_at = now();
            $locked->save();

            CodeListPublished::dispatch($locked);

            return $locked;
        });

        resolve(RequestMemo::class)->forget('masterdata.codes.'.$published->code_list_id.'.');

        return $published;
    }
}
