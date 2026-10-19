<?php

namespace App\Services;

class FormAmount
{
    /** Preserve canonical decimals from hidden inputs; also accept legacy Indonesian grouping. */
    public static function normalize(mixed $value): mixed
    {
        if (!is_string($value)) return $value;
        $value = trim($value);
        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/D', $value)) {
            return str_replace(',', '.', str_replace('.', '', $value));
        }
        if (preg_match('/^\d+,\d{1,2}$/D', $value)) return str_replace(',', '.', $value);
        // Numeric validation remains responsible for malformed and negative input.
        return $value;
    }
}
