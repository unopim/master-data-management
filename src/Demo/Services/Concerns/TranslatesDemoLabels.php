<?php

namespace Webkul\MasterDataManagement\Demo\Services\Concerns;

trait TranslatesDemoLabels
{
    /**
     * The attribute keyed by every active locale, so the demo reads the same whichever locale the admin uses.
     *
     * @return array<string, array<string, string>>
     */
    protected function translated(string $attribute, string $value): array
    {
        return core()->getAllActiveLocales()
            ->pluck('code')
            ->mapWithKeys(fn (string $locale): array => [$locale => [$attribute => $value]])
            ->all();
    }
}
