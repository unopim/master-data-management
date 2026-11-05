<?php

namespace Webkul\MasterDataManagement\Services;

use Normalizer as IntlNormalizer;

/**
 * Folds text into its searchable form: case, accents, punctuation and spacing differences compare equal.
 */
class Normalizer
{
    /**
     * Normalise the text, so `'  Línka–3 '` becomes `'linka 3'` and `'PL-01'` equals `'pl 01'`.
     */
    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim(mb_scrub($value, 'UTF-8')), 'UTF-8');

        $decomposed = IntlNormalizer::normalize($value, IntlNormalizer::FORM_KD);

        $value = preg_replace('/\p{Mn}+/u', '', $decomposed === false ? $value : $decomposed) ?? $value;

        return trim(preg_replace('/[\p{P}\p{Z}\s]+/u', ' ', $value) ?? $value);
    }
}
