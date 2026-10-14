<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndonesianHolidayService
{
    public function getHolidays(int $year): array
    {
        $key = "id_holidays_{$year}";
        if (Cache::has($key)) {
            return Cache::get($key);
        }
        if (! Cache::has($key.'_retry')) {
            try {
                $response = Http::timeout(5)->get('https://api-hari-libur.vercel.app/api', ['year' => $year]);
                $items = $response->json('data');
                if (! $response->successful() || ! is_array($items) || $items === []) {
                    throw new \RuntimeException('Holiday service returned no valid calendar.');
                }
                $holidays = [];
                foreach ($items as $item) {
                    $date = $item['date'] ?? null;
                    if (! is_string($date) || ! preg_match('/^'.$year.'-\d{2}-\d{2}$/', $date)) {
                        continue;
                    }
                    $holidays[$date] = $item['description'] ?? $item['name'] ?? 'Hari Libur Nasional';
                }
                if (! $holidays) {
                    throw new \RuntimeException('Holiday calendar is empty.');
                }
                Cache::put($key, $holidays, now()->addDay());
                Cache::forever($key.'_last_good', $holidays);
                Cache::forget($key.'_warning');

                return $holidays;
            } catch (\Throwable $e) {
                Cache::put($key.'_retry', true, now()->addMinutes(5));
                Cache::put($key.'_warning', true, now()->addMinutes(10));
                Log::warning('Holiday synchronization failed; using last valid calendar.', ['year' => $year]);
            }
        }
        if (app()->bound('session') && request()->hasSession()) {
            session()->flash('warning', 'Sinkronisasi hari libur gagal. Kalender memakai data terakhir jika tersedia; periksa sebelum memproses penggajian.');
        }

        return Cache::get($key.'_last_good', []);
    }

    public function getHolidaysForMonth(string $monthString): array
    {
        return array_filter($this->getHolidays((int) substr($monthString, 0, 4)),
            fn ($date) => str_starts_with($date, $monthString), ARRAY_FILTER_USE_KEY);
    }
}
