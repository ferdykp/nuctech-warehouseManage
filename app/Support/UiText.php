<?php

namespace App\Support;

class UiText
{
    public static function number(int|float|string|null $value): string
    {
        $number = (float) ($value ?? 0);
        return number_format($number, round($number, 2) == round($number) ? 0 : 2, ',', '.');
    }

    /** Translate display values without changing stored values or permissions. */
    public static function label(?string $value): string
    {
        return match (strtolower($value ?? '')) {
            'superadmin' => 'Superadmin',
            'administration' => 'Administrasi',
            'team_leader' => 'Ketua Tim',
            'station_master' => 'Kepala Stasiun',
            'manager' => 'Manajer',
            'employee_role', 'employee', 'staff' => 'Karyawan',
            'pending' => 'Menunggu Tanda Tangan Pengaju',
            'pending_leader' => 'Menunggu Ketua Tim',
            'pending_station' => 'Menunggu Kepala Stasiun',
            'pending_manager' => 'Menunggu Manajer',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'new' => 'Baru',
            'used', 'used_good', 'used good', 'used-good' => 'Bekas Baik',
            'damaged' => 'Rusak',
            'repair', 'repaired' => 'Hasil Perbaikan',
            'transportation' => 'Transportasi',
            'delivery' => 'Pengiriman',
            'office' => 'Perlengkapan Kantor',
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'permanent' => 'Tetap',
            'contract' => 'Kontrak',
            'probation' => 'Masa Percobaan',
            'daily' => 'Harian',
            'resigned' => 'Berhenti Bekerja',
            'regular salary' => 'Gaji Reguler',
            '1st probation' => 'Masa Percobaan 1',
            '2nd probation' => 'Masa Percobaan 2',
            '3rd probation' => 'Masa Percobaan 3',
            'yes' => 'Ya',
            'no' => 'Tidak',
            default => $value ?? '-',
        };
    }
}
