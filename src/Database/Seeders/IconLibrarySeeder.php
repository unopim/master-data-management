<?php

namespace Webkul\MasterDataManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Webkul\MasterDataManagement\Repositories\IconRepository;
use Webkul\MasterDataManagement\Services\IconStore;

class IconLibrarySeeder extends Seeder
{
    /**
     * The packaged master data icons: Lucide outline icons (ISC licence, see the LICENSE beside them).
     */
    public const string SOURCE_DIRECTORY = __DIR__.'/../../Resources/icons';

    /**
     * Add every packaged icon the library does not hold yet; a rerun skips the codes already there.
     */
    public function run(IconRepository $iconRepository, IconStore $iconStore): void
    {
        $existing = $iconRepository->pluck('code')->all();

        foreach (glob(self::SOURCE_DIRECTORY.'/*.svg') ?: [] as $source) {
            $code = str_replace('-', '_', pathinfo($source, PATHINFO_FILENAME));

            if (in_array($code, $existing, true)) {
                continue;
            }

            $iconStore->save(
                ['code' => $code, 'name' => trans('masterdata::app.icons.library.'.$code)],
                new File($source),
                null,
                fn (array $payload) => $iconRepository->create($payload),
            );
        }
    }
}
