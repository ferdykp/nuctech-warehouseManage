<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Sumber tunggal data hari libur nasional & cuti bersama Indonesia,
 * dipakai bersama oleh ScheduleController dan AttendanceController
 * supaya tanggal merah yang tampil di kedua halaman selalu konsisten.
 */
class IndonesianHolidayService
{
    /**
     * @return array<string, string> ['Y-m-d' => 'Nama Hari Libur']
     */
    public function getHolidays(int $year): array
    {
        return Cache::remember("id_holidays_{$year}", now()->addDay(), function () use ($year) {
            try {
                $response = Http::timeout(5)->get('https://api-hari-libur.vercel.app/api', [
                    'year' => $year,
                ]);

                if ($response->successful()) {
                    $holidays = [];
                    $items = $response->json('data', []);
                    foreach ($items as $item) {
                        $date = $item['date'] ?? null;
                        if (!$date) continue;
                        $holidays[$date] = $item['description'] ?? ($item['name'] ?? 'Hari Libur Nasional');
                    }
                    return $holidays;
                }
            } catch (\Throwable $e) {
                // API eksternal gagal diakses (timeout/down) -> fallback ke array kosong,
                // tanggal merah tetap jalan normal untuk Sabtu/Minggu saja.
            }

            return [];
        });
    }

    /**
     * Ambil hari libur yang jatuh di satu bulan tertentu saja (format month: "Y-m").
     * @return array<string, string> ['Y-m-d' => 'Nama Hari Libur']
     */
    public function getHolidaysForMonth(string $monthString): array
    {
        $year = (int) substr($monthString, 0, 4);

        return array_filter(
            $this->getHolidays($year),
            fn($date) => str_starts_with($date, $monthString),
            ARRAY_FILTER_USE_KEY
        );
    }
}
