<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Multi-sheet import untuk data Sparepart.
 * 
 * Mendukung file Excel dengan banyak sheet — setiap sheet akan diproses
 * secara independen menggunakan SparepartSheetImport.
 */
class SparepartImport implements WithMultipleSheets
{
    protected $siteId;
    protected $filename;
    protected $sheetNames;

    /** @var SparepartSheetImport[] */
    protected $sheetImports = [];

    /**
     * @param int    $siteId     ID site tujuan import
     * @param string $filename   Nama file asli untuk tracking
     * @param array  $sheetNames Daftar nama sheet dari spreadsheet
     */
    public function __construct(int $siteId, ?string $filename = null, array $sheetNames = [])
    {
        $this->siteId = $siteId;
        $this->filename = $filename;
        $this->sheetNames = $sheetNames;

        // Buat import handler untuk setiap sheet
        foreach ($sheetNames as $index => $name) {
            $import = new SparepartSheetImport($this->siteId, $this->filename, $name);
            $this->sheetImports[$index] = $import;
        }
    }

    /**
     * Registrasi handler untuk setiap sheet berdasarkan index.
     */
    public function sheets(): array
    {
        return $this->sheetImports;
    }

    /**
     * Ambil summary import dari semua sheet.
     */
    public function getSummary(): array
    {
        $totalImported = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $sheetDetails = [];

        foreach ($this->sheetImports as $index => $import) {
            $sheetName = $this->sheetNames[$index] ?? "Sheet {$index}";
            $totalImported += $import->imported;
            $totalSkipped += $import->skipped;
            $allErrors = array_merge($allErrors, $import->errors);

            if ($import->imported > 0 || $import->skipped > 0) {
                $sheetDetails[$sheetName] = [
                    'imported' => $import->imported,
                    'skipped'  => $import->skipped,
                ];
            }
        }

        return [
            'total_imported' => $totalImported,
            'total_skipped'  => $totalSkipped,
            'errors'         => $allErrors,
            'sheets'         => $sheetDetails,
        ];
    }
}