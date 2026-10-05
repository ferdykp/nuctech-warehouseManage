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
        $currentCategory = 'office'; // Default fallback
        $user = auth()->user();
        $userRole = strtolower($user->role ?? 'employee_role');
        $initialStatus = ReimbursementAccess::approvalStatus($userRole);

        // Jika file kosong / baris kurang dari 3 (Header ada di baris 1 & 2)
        if ($rows->count() < 3) {
            return;
        }

        // Mulai pembacaan data dari baris indeks ke-2 (Baris ke-3 di Excel)
        foreach ($rows->slice(2) as $row) {

            // Konversi baris ke array jika belum
            $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;

            // 1. Cek Kategori (Kolom B / Indeks 1)
            $catInput = isset($rowArray[1]) ? trim(strtolower((string) $rowArray[1])) : '';
            if (!empty($catInput)) {
                if (str_contains($catInput, 'transport')) {
                    $currentCategory = 'transportation';
                } elseif (str_contains($catInput, 'deliver')) {
                    $currentCategory = 'delivery';
                } elseif (str_contains($catInput, 'office')) {
                    $currentCategory = 'office';
                }
            }

            // 2. Cek apakah ini Baris Total / Footer (Kolom D / Indeks 3)
            $colD = isset($rowArray[3]) ? trim(strtolower((string) $rowArray[3])) : '';
            if (str_contains($colD, 'total amount') || str_contains($colD, 'exchange rate')) {
                break; // Berhenti membaca karena sudah masuk area footer
            }

            // 3. Ambil Nama Person (Kolom F / Indeks 5) dan Amount (Kolom G / Indeks 6)
            $personName = isset($rowArray[5]) ? trim((string) $rowArray[5]) : '';
            $rawAmount  = isset($rowArray[6]) ? $rowArray[6] : null;

            // Baris diabaikan jika nama kosong atau tidak ada nominal
            if ($personName === '' || $rawAmount === null || $rawAmount === '') {
                continue;
            }

            // Bersihkan Amount dari karakter non-numerik (seperti 'IDR', 'Rp', koma, titik ribuan)
            $cleanedAmount = preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string) $rawAmount));
            $amount = floatval($cleanedAmount);

            if ($amount <= 0) {
                continue;
            }

            // 4. Parsing Tanggal (Kolom C / Indeks 2)
            $rawDate = isset($rowArray[2]) ? $rowArray[2] : null;
            $parsedDate = now()->format('Y-m-d');

            if (!empty($rawDate)) {
                if (is_numeric($rawDate)) {
                    // Jika format serial tanggal Excel
                    try {
                        $parsedDate = Carbon::instance(Date::excelToDateTimeObject($rawDate))->format('Y-m-d');
                    } catch (\Exception $e) {
                        $parsedDate = now()->format('Y-m-d');
                    }
                } else {
                    try {
                        $parsedDate = Carbon::parse((string) $rawDate)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $parsedDate = now()->format('Y-m-d');
                    }
                }
            }

            // 5. Ambil data lokasi & catatan
            $fromLocation = isset($rowArray[3]) ? trim((string) $rowArray[3]) : '';
            $toLocation   = isset($rowArray[4]) ? trim((string) $rowArray[4]) : '';
            $comment      = isset($rowArray[7]) ? trim((string) $rowArray[7]) : '';

            // Simpan ke Database
            Reimbursement::create([
                'user_id'            => $user->id,
                'person_name'        => $personName,
                'date'               => $parsedDate,
                'category'           => $currentCategory,
                'from_location'      => in_array($currentCategory, ['transportation', 'delivery']) && $fromLocation !== '-' && $fromLocation !== '' ? $fromLocation : null,
                'to_location'        => in_array($currentCategory, ['transportation', 'delivery']) && $toLocation !== '-' && $toLocation !== '' ? $toLocation : null,
                'amount'             => $amount,
                'comment'            => ($comment !== '-' && $comment !== '') ? $comment : null,
                'receipt_attachment' => null, // Lampiran fisik dapat diunggah kemudian saat edit
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
