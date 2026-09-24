<?php

namespace App\Exports;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Report::orderByDesc('failure_date')->get(['id', 'attendant', 'site_machine', 'failure_date', 'failure_note', 'ts_procedure']);
    }

    public function headings(): array
    {
        return ['ID', 'Attendant', 'Site', 'Failure Date', 'Failure Note', 'Troubleshooting Procedure'];
    }
}
