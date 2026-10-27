<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class EmployeeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_separates_resigned_employees_and_preserves_identifiers(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 27));
        $branch = Branch::create(['branch_name' => 'Jakarta', 'branch_code' => 'JKT']);
        $site = Site::create(['branch_id' => $branch->id, 'machine_name' => 'Office', 'slug' => 'office', 'location' => 'Jakarta']);
        foreach ([
            ['name' => 'Active Employee', 'status' => 'Contract', 'is_active' => true],
            ['name' => 'Resigned Employee', 'status' => 'Resigned', 'is_active' => false, 'resign_date' => '2026-09-30'],
            ['name' => 'Inactive Employee', 'status' => 'Contract', 'is_active' => false],
            ['name' => 'Old Resignation', 'status' => 'Contract', 'is_active' => true, 'resign_date' => '2026-10-01'],
            ['name' => 'Future Resignation', 'status' => 'Contract', 'is_active' => true, 'resign_date' => '2026-11-01'],
        ] as $index => $attributes) {
            Employee::create($attributes + ['site_id' => $site->id, 'branch_id' => $branch->id, 'nik' => '001234567890123'.$index, 'phone_number' => '08123456789',
                'join_date' => '2025-01-01', 'mcu' => 'no', 'tld' => 'no']);
        }

        $path = tempnam(sys_get_temp_dir(), 'employee-export-');
        try {
            file_put_contents($path, Excel::raw(new EmployeesExport, \Maatwebsite\Excel\Excel::XLSX));
            $book = IOFactory::load($path);
            $this->assertSame(['Karyawan Aktif', 'Karyawan Resign'], $book->getSheetNames());
            $active = $book->getSheet(0)->toArray();
            $resigned = $book->getSheet(1)->toArray();
            $this->assertSame(['Active Employee', 'Future Resignation'], array_column(array_slice($active, 2), 1));
            $this->assertSame(['Inactive Employee', 'Old Resignation', 'Resigned Employee'], array_column(array_slice($resigned, 2), 1));
            $this->assertSame('Total: 2 Person', $active[0][1]);
            $this->assertSame('Total: 3 Person', $resigned[0][1]);
            $this->assertSame('1', $active[2][0]);
            $this->assertSame('1', $resigned[2][0]);
            $this->assertSame('0012345678901232', $resigned[2][2]);
            $this->assertSame('08123456789', $resigned[2][3]);
            $this->assertSame('Resign - 2026-09-30', $resigned[4][13]);
            $this->assertSame('1 Y / 8 M / 29 D', $resigned[4][10]);
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
    }

    public function test_empty_export_still_has_both_sheets_with_zero_totals(): void
    {
        $sheets = (new EmployeesExport)->sheets();
        foreach ($sheets as $sheet) {
            $this->assertCount(0, $sheet->collection());
            $this->assertSame('Total: 0 Person', $sheet->headings()[0][1]);
        }
    }
}
