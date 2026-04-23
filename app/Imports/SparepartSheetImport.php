<?php

namespace App\Imports;

use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import satu sheet Excel ke data Sparepart.
 * 
 * Mendukung berbagai format header kolom dengan auto-detect mapping.
 * Kolom yang tidak ditemukan akan diisi dengan default value.
 */
class SparepartSheetImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    protected $siteId;
    protected $filename;
    protected $sheetName;
    protected $columnMap = [];
    protected $headerDetected = false;

    // Import statistics
    public $imported = 0;
    public $skipped = 0;
    public $errors = [];

    /**
     * Mapping alias: key = nama field database, value = array kemungkinan nama kolom di Excel.
     * Maatwebsite WithHeadingRow mengubah header menjadi lowercase slug (spasi → underscore).
     */
    protected static $columnAliases = [
        'item_name' => [
            'item_name', 'item', 'items', 'name', 'device_name', 'nama_item',
            'nama_barang', 'barang', 'spare_part', 'sparepart', 'part_name',
            'description', 'deskripsi', 'item_description',
        ],
        'type' => [
            'type', 'model_no', 'model_number', 'model', 'tipe',
            'part_number', 'part_no', 'pn', 'type_model',
        ],
        'uom' => [
            'uom', 'unit', 'satuan', 'unit_of_measure',
        ],
        'qty' => [
            'qty', 'quantity', 'jumlah', 'stock', 'stok', 'amount',
        ],
        'condition' => [
            'condition', 'status', 'kondisi', 'state',
        ],
        'serial_number' => [
            'serial_number', 'sn', 'serial', 'serial_no', 'nomor_seri',
        ],
        'note' => [
            'note', 'notes', 'comment', 'comments', 'keterangan',
            'remark', 'remarks', 'catatan',
        ],
    ];

    public function __construct(int $siteId, ?string $filename = null, ?string $sheetName = null)
    {
        $this->siteId = $siteId;
        $this->filename = $filename;
        $this->sheetName = $sheetName;
    }

    /**
     * Deteksi mapping kolom berdasarkan header row yang ada di Excel.
     */
    protected function detectColumns(array $row): void
    {
        if ($this->headerDetected) {
            return;
        }

        $excelColumns = array_keys($row);

        foreach (self::$columnAliases as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (in_array($alias, $excelColumns)) {
                    $this->columnMap[$field] = $alias;
                    break;
                }
            }
        }

        $this->headerDetected = true;
    }

    /**
     * Ambil nilai dari row berdasarkan mapping kolom yang sudah di-detect.
     */
    protected function getMappedValue(array $row, string $field, $default = null)
    {
        if (!isset($this->columnMap[$field])) {
            return $default;
        }

        $value = $row[$this->columnMap[$field]] ?? $default;

        // Skip formula errors (#VALUE!, #REF!, dll)
        if (is_string($value) && str_starts_with($value, '#')) {
            return $default;
        }

        return $value;
    }

    /**
     * Cek apakah row ini valid untuk di-import (minimal harus ada item_name atau type).
     */
    protected function isValidRow(array $row): bool
    {
        $itemName = $this->getMappedValue($row, 'item_name');
        $type = $this->getMappedValue($row, 'type');

        // Minimal harus ada salah satu: item_name atau type
        if (empty($itemName) && empty($type)) {
            return false;
        }

        // Qty harus valid (angka > 0), atau tidak ada (default ke 1)
        $qty = $this->getMappedValue($row, 'qty');
        if ($qty !== null && (!is_numeric($qty) || (int)$qty <= 0)) {
            // Jika qty berisi "-" atau teks non-numerik, skip
            if ($qty !== null && $qty !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalize condition value ke enum yang valid di database.
     */
    protected function normalizeCondition($value): string
    {
        if (empty($value)) {
            return 'new';
        }

        $value = strtolower(trim($value));
        $validConditions = ['new', 'used-good', 'damaged', 'repair'];

        if (in_array($value, $validConditions)) {
            return $value;
        }

        // Map variasi umum
        $conditionMap = [
            'ok'        => 'used-good',
            'good'      => 'used-good',
            'bagus'     => 'used-good',
            'baik'      => 'used-good',
            'used'      => 'used-good',
            'bekas'     => 'used-good',
            'baru'      => 'new',
            'rusak'     => 'damaged',
            'broken'    => 'damaged',
            'bad'       => 'damaged',
            'repaired'  => 'repair',
            'perbaikan' => 'repair',
            'fixed'     => 'repair',
        ];

        return $conditionMap[$value] ?? 'new';
    }

    /**
     * Generate serial number otomatis jika tidak ada di Excel.
     */
    protected function generateSerialNumber(array $row): string
    {
        $type = $this->getMappedValue($row, 'type', '');
        $prefix = $type ? Str::upper(Str::slug(Str::limit($type, 20, ''), '')) : 'IMP';
        return $prefix . '-' . now()->format('ymd') . '-' . Str::random(5);
    }

    public function model(array $row)
    {
        // Detect kolom pada row pertama
        $this->detectColumns($row);

        // Skip row kosong / tidak valid
        if (!$this->isValidRow($row)) {
            $this->skipped++;
            return null;
        }

        try {
            return DB::transaction(function () use ($row) {
                $itemName = $this->getMappedValue($row, 'item_name', '');
                $type = $this->getMappedValue($row, 'type', '');

                // Jika item_name kosong tapi type ada, gunakan type sebagai item_name
                if (empty($itemName) && !empty($type)) {
                    $itemName = $type;
                }

                // Jika type kosong tapi item_name ada, gunakan item_name sebagai type
                if (empty($type) && !empty($itemName)) {
                    $type = $itemName;
                }

                $serialNumber = $this->getMappedValue($row, 'serial_number');
                // S No. biasanya nomor urut (1, 2, 3...), bukan serial number asli
                if (!empty($serialNumber) && is_numeric($serialNumber) && (int)$serialNumber < 1000) {
                    $serialNumber = null; // Abaikan nomor urut
                }
                // Jika tidak ada SN, gunakan type/model number sebagai serial number
                if (empty($serialNumber)) {
                    $serialNumber = !empty($type) ? trim($type) : trim($itemName);
                }
                // Pastikan serial number unik — jika sudah ada, tambah suffix
                $baseSN = $serialNumber;
                $counter = 1;
                while (Sparepart::where('serial_number', $serialNumber)->exists()) {
                    $existing = Sparepart::where('serial_number', $serialNumber)->first();
                    // Jika item sudah ada dengan SN yang sama, gunakan SN itu (akan di-update)
                    if ($existing->item_name === $itemName || $existing->type === $type) {
                        break;
                    }
                    $counter++;
                    $serialNumber = $baseSN . '-' . $counter;
                }

                $uom = $this->getMappedValue($row, 'uom', 'PCS');
                $qty = (int)($this->getMappedValue($row, 'qty', 1) ?: 1);
                $condition = $this->normalizeCondition($this->getMappedValue($row, 'condition'));
                $note = $this->getMappedValue($row, 'note');

                $sourceLabel = 'Import Excel: ' . $this->filename;
                if ($this->sheetName) {
                    $sourceLabel .= ' [' . $this->sheetName . ']';
                }

                // 1. Update atau Create data sparepart berdasarkan Serial Number
                $sparepart = Sparepart::updateOrCreate(
                    ['serial_number' => $serialNumber],
                    [
                        'item_name'   => $itemName,
                        'type'        => $type,
                        'uom'         => strtoupper(trim($uom)),
                        'note'        => $note,
                        'source_data' => $sourceLabel,
                    ]
                );

                // 2. Simpan Stok ke Site terkait
                SparepartStock::updateOrCreate(
                    [
                        'sparepart_id' => $sparepart->id,
                        'site_id'      => $this->siteId,
                        'condition'    => $condition,
                    ],
                    ['qty' => $qty]
                );

                // 3. Catat History sebagai 'IMPORT'
                SparepartHistory::create([
                    'sparepart_id' => $sparepart->id,
                    'to_site_id'   => $this->siteId,
                    'action'       => 'CREATE',
                    'condition'    => $condition,
                    'qty'          => $qty,
                    'note'         => 'Import via Excel' . ($this->sheetName ? ' [' . $this->sheetName . ']' : ''),
                ]);

                $this->imported++;
                return $sparepart;
            });
        } catch (\Exception $e) {
            $this->errors[] = 'Row error: ' . $e->getMessage();
            $this->skipped++;
            return null;
        }
    }
}
