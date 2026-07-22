<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceExport implements WithMultipleSheets
{
    protected $branchId;
    protected $month;

    public function __construct($branchId, $month)
    {
        $this->branchId = $branchId;
        $this->month = $month;
    }

    public function sheets(): array
    {
        return [
            new Sheets\AttendanceFix($this->branchId, $this->month),  // Sheet 2: Ringkasan Total
            new Sheets\AttendanceDetailSheet($this->branchId, $this->month),   // Sheet 1: Detail Harian (1-1, 1-2, 1-3, dst)
            new Sheets\AttendanceSummarySheet($this->branchId, $this->month),  // Sheet 2: Ringkasan Total

        ];
    }
}
