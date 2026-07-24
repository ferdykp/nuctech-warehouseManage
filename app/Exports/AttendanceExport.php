<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceExport implements WithMultipleSheets
{
    protected $siteId;
    protected $month;

    public function __construct($siteId, $month)
    {
        $this->siteId = $siteId;
        $this->month = $month;
    }

    public function sheets(): array
    {
        // Jika site_id = 'all' atau kosong, tampilkan sheet gabungan/semua site
        if ($this->siteId === 'all' || empty($this->siteId)) {
            return [
                new Sheets\AttendanceFix($this->siteId, $this->month),          // Sheet 1: Per site tanpa kolom site
                new Sheets\AttendanceDetailSheet(null, $this->month), // Semua Site
            ];
        }

        return [
            new Sheets\AttendanceFix($this->siteId, $this->month),          // Sheet 1: Per site tanpa kolom site
            new Sheets\AttendanceDetailSheet($this->siteId, $this->month),   // Sheet 2: Per site dengan kolom site
        ];
    }
}
