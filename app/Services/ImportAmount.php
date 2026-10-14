<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ImportAmount
{
    public static function parse(mixed $value, int $row): float
    {
        if (is_int($value) || is_float($value)) {
            $amount = (float) $value;
        } else {
            $text = preg_replace('/^(?:Rp\.?|IDR)\s*/i', '', trim((string) $value));
            // Indonesian text: 1.000.000 or 1.000.000,50. Numeric Excel cells stay numeric.
            if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/', $text)) {
                $text = str_replace(',', '.', str_replace('.', '', $text));
            } elseif (preg_match('/^\d+(?:,\d{1,2})?$/', $text)) {
                $text = str_replace(',', '.', $text);
            } elseif (! preg_match('/^\d+\.\d{1,2}$/', $text)) {
                throw ValidationException::withMessages(['file' => "Nominal baris {$row} tidak valid. Gunakan angka Excel atau format 1.000.000,50."]);
            }
            $amount = (float) $text;
        }
        if (! is_finite($amount) || $amount <= 0 || $amount > 999999999999.99) {
            throw ValidationException::withMessages(['file' => "Nominal baris {$row} harus positif dan dalam batas yang didukung."]);
        }

        return round($amount, 2);
    }
}
