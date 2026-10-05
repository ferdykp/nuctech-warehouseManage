<?php

namespace App\Imports;

use App\Models\Reimbursement;
use App\Services\ReimbursementAccess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReimbursementImport implements ToCollection, WithCalculatedFormulas
{
    protected $importedCount = 0;

    public function collection(Collection $rows)
    {
        $currentCategory = 'transportation'; // Default category fallback
        $user = auth()->user();
        $userRole = strtolower($user->role ?? 'employee_role');
        $initialStatus = ReimbursementAccess::approvalStatus($userRole);

        // Jika isi file kurang dari 3 baris (Header baris 1 & 2)
        if ($rows->count() < 3) {
            return;
        }

        // Mulai membaca dari baris indeks ke-2 (Baris ke-3 di Excel)
        foreach ($rows->slice(2) as $row) {

            $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;

            // 1. CEK & SIMPAN KATEGORI (Mendukung Cell Merged)
            $catInput = isset($rowArray[1]) && $rowArray[1] !== null ? trim(strtolower((string) $rowArray[1])) : '';
            if (!empty($catInput)) {
                if (str_contains($catInput, 'transport')) {
                    $currentCategory = 'transportation';
                } elseif (str_contains($catInput, 'deliver')) {
                    $currentCategory = 'delivery';
                } elseif (str_contains($catInput, 'office')) {
                    $currentCategory = 'office';
                }
            }

            // 2. DETEKSI BARIS FOOTER / TOTAL AMOUNT (Stop membaca jika mencapai area total)
            $colD = isset($rowArray[3]) && $rowArray[3] !== null ? trim(strtolower((string) $rowArray[3])) : '';
            if (str_contains($colD, 'total amount') || str_contains($colD, 'exchange rate')) {
                break;
            }

            // 3. AMBIL NAMA & NOMINAL
            $personName = isset($rowArray[5]) && $rowArray[5] !== null ? trim((string) $rowArray[5]) : '';
            $rawAmount  = isset($rowArray[6]) ? $rowArray[6] : null;

            // LEWATI jika nama person kosong atau tidak ada nominal (misal pada baris header kategori kosong)
            if ($personName === '' || $rawAmount === null || $rawAmount === '') {
                continue;
            }

            // Bersihkan format angka nominal (menghapus string IDR, Rp, koma, titik)
            $cleanedAmount = preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $rawAmount));
            $amount = floatval($cleanedAmount);

            if ($amount <= 0) {
                continue;
            }

            // 4. PARSING TANGGAL
            $rawDate = isset($rowArray[2]) ? $rowArray[2] : null;
            $parsedDate = now()->format('Y-m-d');

            if (!empty($rawDate)) {
                if (is_numeric($rawDate)) {
                    try {
                        $parsedDate = Carbon::instance(Date::excelToDateTimeObject($rawDate))->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $parsedDate = now()->format('Y-m-d');
                    }
                } else {
                    try {
                        $parsedDate = Carbon::parse((string) $rawDate)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $parsedDate = now()->format('Y-m-d');
                    }
                }
            }

            // 5. AMBIL DETAIL LOKASI & CATATAN
            $fromLocation = isset($rowArray[3]) && $rowArray[3] !== null ? trim((string) $rowArray[3]) : '';
            $toLocation   = isset($rowArray[4]) && $rowArray[4] !== null ? trim((string) $rowArray[4]) : '';
            $comment      = isset($rowArray[7]) && $rowArray[7] !== null ? trim((string) $rowArray[7]) : '';

            // Simpan Data Ke Database
            Reimbursement::create([
                'user_id'            => $user->id,
                'person_name'        => $personName,
                'date'               => $parsedDate,
                'category'           => $currentCategory,
                'from_location'      => in_array($currentCategory, ['transportation', 'delivery']) && $fromLocation !== '-' && $fromLocation !== '' ? $fromLocation : null,
                'to_location'        => in_array($currentCategory, ['transportation', 'delivery']) && $toLocation !== '-' && $toLocation !== '' ? $toLocation : null,
                'amount'             => $amount,
                'comment'            => ($comment !== '-' && $comment !== '') ? $comment : null,
                'receipt_attachment' => null, // Biarkan null untuk diupload susulan
                'status'             => $initialStatus
            ]);

            $this->importedCount++;
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
