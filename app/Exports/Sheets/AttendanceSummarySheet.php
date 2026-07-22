<?php

namespace App\Exports\Sheets;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceSummarySheet implements FromQuery, WithTitle, WithHeadings, WithMapping
{
    protected $siteid;
    protected $month;
    private $rowNumber = 0;

    public function __construct($siteid, $month)
    {
        $this->siteid = $siteid;
        $this->month = $month;
    }

    public function title(): string
    {
        return 'Summary Total Bulanan';
    }

    public function headings(): array
    {
        return ['No', 'Kantor Branch/Site', 'Nama Lengkap Karyawan', 'Periode Bulan', 'Total Kehadiran', 'Hari Kerja Efektif'];
    }

    public function query()
    {
        return Attendance::query()
            ->with(['employee.site'])
            ->whereHas('employee', function ($q) {
                $q->where('site_id', $this->siteid);
            })
            ->where('month', $this->month);
    }

    public function map($attendance): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            // PERBAIKAN: Menggunakan machine_name menggantikan name
            $attendance->employee->site->machine_name ?? '-',
            $attendance->employee->name ?? 'Karyawan Terhapus',
            \Carbon\Carbon::parse($attendance->month)->translatedFormat('F Y'),
            $attendance->attendance_count . ' Sesi',
            $attendance->working_days . ' Hari Kerja',
        ];
    }
}
