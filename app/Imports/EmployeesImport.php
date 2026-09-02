<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Site;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeesImport implements ToCollection
{
    protected $siteId;

    public function __construct($siteId = null)
    {
        $this->siteId = $siteId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $processedEmailsInSession = [];

        // 1. CARI BARIS HEADER (JUDUL KOLOM) DENGAN KATA KUNCI TERMASUK SALARY / GAJI
        $headerRowIndex = null;
        $columnIndex = [
            'name'        => null,
            'nik'         => null,
            'phone'       => null,
            'email'       => null,
            'position'    => null,
            'salary'      => null,
            'site'        => null,
            'designation' => null,
            'join_date'   => null,
            'mcu'         => null,
            'tld'         => null,
        ];

        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cIdx => $cellValue) {
                if (empty($cellValue)) continue;

                $headerText = strtolower(trim((string) $cellValue));

                if ($columnIndex['name'] === null && (str_contains($headerText, 'name') || str_contains($headerText, 'nama')) && !str_contains($headerText, 'list') && !str_contains($headerText, 'site')) {
                    $columnIndex['name'] = $cIdx;
                    $headerRowIndex = $rIdx;
                }
                if ($columnIndex['nik'] === null && (str_contains($headerText, 'nik') || str_contains($headerText, 'national id') || str_contains($headerText, 'ktp'))) {
                    $columnIndex['nik'] = $cIdx;
                }
                if ($columnIndex['phone'] === null && (str_contains($headerText, 'phone') || str_contains($headerText, 'telp') || str_contains($headerText, 'hp'))) {
                    $columnIndex['phone'] = $cIdx;
                }
                if ($columnIndex['email'] === null && str_contains($headerText, 'email')) {
                    $columnIndex['email'] = $cIdx;
                }
                if ($columnIndex['position'] === null && (str_contains($headerText, 'position') || str_contains($headerText, 'qualification') || str_contains($headerText, 'jabatan'))) {
                    $columnIndex['position'] = $cIdx;
                }
                if ($columnIndex['salary'] === null && (str_contains($headerText, 'salary') || str_contains($headerText, 'gaji') || str_contains($headerText, 'basic'))) {
                    $columnIndex['salary'] = $cIdx;
                }
                if ($columnIndex['site'] === null && (str_contains($headerText, 'site') || str_contains($headerText, 'work site') || str_contains($headerText, 'location'))) {
                    $columnIndex['site'] = $cIdx;
                }
                if ($columnIndex['designation'] === null && (str_contains($headerText, 'designation') || str_contains($headerText, 'branch') || str_contains($headerText, 'cabang'))) {
                    $columnIndex['designation'] = $cIdx;
                }
                if ($columnIndex['join_date'] === null && (str_contains($headerText, 'join') || str_contains($headerText, 'masuk') || str_contains($headerText, 'start'))) {
                    $columnIndex['join_date'] = $cIdx;
                }
                if ($columnIndex['mcu'] === null && str_contains($headerText, 'mcu')) {
                    $columnIndex['mcu'] = $cIdx;
                }
                if ($columnIndex['tld'] === null && str_contains($headerText, 'tld')) {
                    $columnIndex['tld'] = $cIdx;
                }
            }

            if ($headerRowIndex !== null) {
                break;
            }
        }

        // Fallback jika tidak terdeteksi header secara otomatis
        if ($headerRowIndex === null) {
            $headerRowIndex = 1;
            $columnIndex = [
                'name' => 1,
                'nik' => 2,
                'phone' => 3,
                'email' => 4,
                'position' => 5,
                'site' => 6,
                'designation' => 7,
                'join_date' => 8,
                'mcu' => 12,
                'tld' => 13
            ];
        }

        $allSites = Site::with('branch')->get();
        $defaultSite = Site::first();

        // 2. PROSES SEMUA BARIS KARYAWAN
        foreach ($rows as $rIdx => $row) {
            if ($rIdx <= $headerRowIndex) {
                continue; // Skip baris header
            }

            $nameRaw = isset($columnIndex['name'], $row[$columnIndex['name']]) ? trim((string)$row[$columnIndex['name']]) : null;

            // Skip jika nama kosong, nomor saja, atau kata sampah
            if (empty($nameRaw) || is_numeric($nameRaw) || str_contains(strtolower($nameRaw), 'updated') || str_contains(strtolower($nameRaw), 'by ') || str_contains($nameRaw, '=ROW') || $nameRaw === 'ID' || $nameRaw === 'Name') {
                continue;
            }

            // Ambil data mentah per baris
            $nikRaw      = isset($columnIndex['nik'], $row[$columnIndex['nik']]) ? trim((string)$row[$columnIndex['nik']]) : null;
            $phoneRaw    = isset($columnIndex['phone'], $row[$columnIndex['phone']]) ? trim((string)$row[$columnIndex['phone']]) : '-';
            $emailRaw    = isset($columnIndex['email'], $row[$columnIndex['email']]) ? trim((string)$row[$columnIndex['email']]) : null;
            $positionRaw = isset($columnIndex['position'], $row[$columnIndex['position']]) ? trim((string)$row[$columnIndex['position']]) : null;
            $salaryRaw   = isset($columnIndex['salary'], $row[$columnIndex['salary']]) ? trim((string)$row[$columnIndex['salary']]) : null;
            $siteNameRaw = isset($columnIndex['site'], $row[$columnIndex['site']]) ? trim((string)$row[$columnIndex['site']]) : null;
            $designation = isset($columnIndex['designation'], $row[$columnIndex['designation']]) ? trim((string)$row[$columnIndex['designation']]) : null;
            $joinDateRaw = isset($columnIndex['join_date'], $row[$columnIndex['join_date']]) ? trim((string)$row[$columnIndex['join_date']]) : null;
            $mcuRaw      = isset($columnIndex['mcu'], $row[$columnIndex['mcu']]) ? strtolower(trim((string)$row[$columnIndex['mcu']])) : 'no';
            $tldRaw      = isset($columnIndex['tld'], $row[$columnIndex['tld']]) ? strtolower(trim((string)$row[$columnIndex['tld']])) : 'no';

            // 1. Sanitisasi NIK
            $nik = null;
            if ($nikRaw) {
                if (is_numeric($nikRaw)) {
                    $nikRaw = sprintf('%.0f', (float)$nikRaw);
                }
                $cleanedNik = preg_replace('/[^0-9]/', '', $nikRaw);
                if (!empty($cleanedNik)) {
                    $nik = substr($cleanedNik, 0, 16);
                }
            }

            // 2. Sanitisasi Salary / Gaji Pokok (Hapus Rp, titik, koma)
            $basicSalary = 0;
            if (!empty($salaryRaw)) {
                $cleanedSalary = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $salaryRaw));
                if (is_numeric($cleanedSalary)) {
                    $basicSalary = (float)$cleanedSalary;
                }
            }

            // 3. Smart Site Matching
            $matchedSite = null;
            if ($siteNameRaw) {
                $cleanSite = strtolower(str_replace(['/', '-', ' '], '', $siteNameRaw));

                foreach ($allSites as $siteItem) {
                    $dbSiteName = strtolower(str_replace(['/', '-', ' '], '', $siteItem->machine_name));
                    $dbBranchName = $siteItem->branch ? strtolower(trim($siteItem->branch->branch_name)) : '';

                    if (str_contains($cleanSite, $dbSiteName) || str_contains($dbSiteName, $cleanSite)) {
                        $matchedSite = $siteItem;
                        break;
                    }

                    $sitePrefix = explode('/', $siteNameRaw)[0];
                    $cleanPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sitePrefix));

                    if (!empty($cleanPrefix) && str_contains($dbSiteName, substr($cleanPrefix, 0, 5))) {
                        if ($designation && str_contains(strtolower($designation), $dbBranchName)) {
                            $matchedSite = $siteItem;
                            break;
                        }
                    }
                }
            }

            if (!$matchedSite && $this->siteId) {
                $matchedSite = Site::find($this->siteId);
            }
            if (!$matchedSite) {
                $matchedSite = $defaultSite;
            }

            $branchId = $matchedSite ? $matchedSite->branch_id : Branch::first()->id;

            // 4. Match Karyawan Eksisting
            $employee = Employee::where('name', $nameRaw)->first();
            if (!$employee && $nik) {
                $employee = Employee::where('nik', $nik)->first();
            }

            // 5. Sanitisasi Email
            $email = null;
            if ($emailRaw && filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                $candidateEmail = strtolower($emailRaw);

                $existingEmailOwner = Employee::where('email', $candidateEmail)->first();
                $isEmailTakenInDb = $existingEmailOwner && (!$employee || $existingEmailOwner->id !== $employee->id);
                $isEmailTakenInSession = in_array($candidateEmail, $processedEmailsInSession);

                if (!$isEmailTakenInDb && !$isEmailTakenInSession) {
                    $email = $candidateEmail;
                    $processedEmailsInSession[] = $candidateEmail;
                }
            }

            // 6. Parse Join Date, Contract Start Date (+3 Bulan), dan Status
            $joinDateCarbon = null;
            if ($joinDateRaw) {
                try {
                    if (is_numeric($joinDateRaw)) {
                        $joinDateCarbon = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($joinDateRaw));
                    } else {
                        $joinDateCarbon = Carbon::parse($joinDateRaw);
                    }
                } catch (\Exception $e) {
                    $joinDateCarbon = Carbon::now();
                }
            } else {
                $joinDateCarbon = Carbon::now();
            }

            $joinDateFormatted = $joinDateCarbon->format('Y-m-d');
            $contractStartDateFormatted = $joinDateCarbon->copy()->addMonths(3)->format('Y-m-d');

            $diffInMonths = $joinDateCarbon->diffInMonths(Carbon::now());
            $calculatedStatus = ($diffInMonths >= 3) ? 'Contract' : 'Probation';

            // 7. Simpan / Update Karyawan
            $payload = [
                'site_id'             => $matchedSite->id,
                'branch_id'           => $branchId,
                'name'                => $nameRaw,
                'nik'                 => $nik,
                'email'               => $email,
                'phone_number'        => $phoneRaw,
                'position'            => is_numeric($positionRaw) ? null : $positionRaw,
                'status'              => $calculatedStatus,
                'basic_salary'        => $basicSalary, // DISIMPAN DARI EXCEL
                'mcu'                 => (str_contains($mcuRaw, 'yes') || str_contains($mcuRaw, 'ya')) ? 'yes' : 'no',
                'tld'                 => (str_contains($tldRaw, 'yes') || str_contains($tldRaw, 'ya')) ? 'yes' : 'no',
                'join_date'           => $joinDateFormatted,
                'contract_start_date' => $contractStartDateFormatted,
                'is_active'           => true,
            ];

            if ($employee) {
                if (empty($payload['email']) && !empty($employee->email)) {
                    unset($payload['email']);
                }
                $employee->update($payload);
            } else {
                Employee::create($payload);
            }
        }
    }
}
