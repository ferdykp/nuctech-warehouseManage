<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\Site;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

        // 1. CARI BARIS HEADER & INDEKS KOLOM
        $headerRowIndex = null;
        $columnIndex = [
            'name'          => null,
            'bank_name'     => null,
            'bank_account'  => null,
            'nik'           => null,
            'phone'         => null,
            'email'         => null,
            'salary'        => null,
            'position'      => null,
            'site'          => null,
            'designation'   => null,
            'join_date'     => null,
            'join_fallback' => null,
            'mcu'           => null,
            'tld'           => null,
        ];

        foreach ($rows as $rIdx => $row) {
            foreach ($row as $cIdx => $cellValue) {
                if (empty($cellValue)) continue;

                $headerText = strtolower(trim((string) $cellValue));

                if ($columnIndex['name'] === null && ($headerText === 'name' || $headerText === 'nama')) {
                    $columnIndex['name'] = $cIdx;
                    $headerRowIndex = $rIdx;
                }
                if ($columnIndex['bank_name'] === null && str_contains($headerText, 'bank name')) {
                    $columnIndex['bank_name'] = $cIdx;
                }
                if ($columnIndex['bank_account'] === null && str_contains($headerText, 'bank account')) {
                    $columnIndex['bank_account'] = $cIdx;
                }
                if ($columnIndex['nik'] === null && str_contains($headerText, 'nik')) {
                    $columnIndex['nik'] = $cIdx;
                }
                if ($columnIndex['phone'] === null && (str_contains($headerText, 'phone') || str_contains($headerText, 'hp') || str_contains($headerText, 'telp'))) {
                    $columnIndex['phone'] = $cIdx;
                }
                if ($columnIndex['email'] === null && str_contains($headerText, 'email')) {
                    $columnIndex['email'] = $cIdx;
                }
                if ($columnIndex['salary'] === null && (str_contains($headerText, 'salary') || str_contains($headerText, 'gaji'))) {
                    $columnIndex['salary'] = $cIdx;
                }
                if ($columnIndex['position'] === null && (str_contains($headerText, 'position') || str_contains($headerText, 'jabatan'))) {
                    $columnIndex['position'] = $cIdx;
                }
                if ($columnIndex['site'] === null && str_contains($headerText, 'site')) {
                    $columnIndex['site'] = $cIdx;
                }
                if ($columnIndex['designation'] === null && (str_contains($headerText, 'designation') || str_contains($headerText, 'branch'))) {
                    $columnIndex['designation'] = $cIdx;
                }

                if ($headerText === 'join') {
                    $columnIndex['join_fallback'] = $cIdx;
                }
                if ($columnIndex['join_date'] === null && str_contains($headerText, 'join date')) {
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

        // Fallback jika header tidak terdeteksi otomatis
        if ($headerRowIndex === null) {
            $headerRowIndex = 0;
            $columnIndex = [
                'name'          => 1,
                'bank_name'     => 2,
                'bank_account'  => 3,
                'nik'           => 4,
                'phone'         => 5,
                'email'         => 6,
                'salary'        => 7,
                'position'      => 8,
                'site'          => 9,
                'designation'   => 10,
                'join_date'     => 11,
                'join_fallback' => 12,
                'mcu'           => 13,
                'tld'           => 14,
            ];
        }

        $allSites = Site::with('branch')->get();
        $defaultSite = Site::first();
        $user = Auth::user();

        // 2. BACA & SIMPAN DATA KARYAWAN
        foreach ($rows as $rIdx => $row) {
            if ($rIdx <= $headerRowIndex) {
                continue;
            }

            $nameRaw = isset($columnIndex['name'], $row[$columnIndex['name']]) ? trim((string)$row[$columnIndex['name']]) : null;

            if (empty($nameRaw) || is_numeric($nameRaw) || str_contains(strtolower($nameRaw), 'updated') || str_contains($nameRaw, '=ROW') || $nameRaw === 'No.' || $nameRaw === 'Name') {
                continue;
            }

            $bankNameRaw    = isset($columnIndex['bank_name'], $row[$columnIndex['bank_name']]) ? trim((string)$row[$columnIndex['bank_name']]) : null;
            $bankAccountRaw = isset($columnIndex['bank_account'], $row[$columnIndex['bank_account']]) ? (string)$row[$columnIndex['bank_account']] : null;
            $nikRaw         = isset($columnIndex['nik'], $row[$columnIndex['nik']]) ? (string)$row[$columnIndex['nik']] : null;
            $phoneRaw       = isset($columnIndex['phone'], $row[$columnIndex['phone']]) ? trim((string)$row[$columnIndex['phone']]) : '-';
            $emailRaw       = isset($columnIndex['email'], $row[$columnIndex['email']]) ? trim((string)$row[$columnIndex['email']]) : null;
            $salaryRaw      = isset($columnIndex['salary'], $row[$columnIndex['salary']]) ? trim((string)$row[$columnIndex['salary']]) : null;
            $positionRaw    = isset($columnIndex['position'], $row[$columnIndex['position']]) ? trim((string)$row[$columnIndex['position']]) : null;
            $siteNameRaw    = isset($columnIndex['site'], $row[$columnIndex['site']]) ? trim((string)$row[$columnIndex['site']]) : null;
            $designation    = isset($columnIndex['designation'], $row[$columnIndex['designation']]) ? trim((string)$row[$columnIndex['designation']]) : null;

            $joinDateRaw = null;
            if (isset($columnIndex['join_fallback'], $row[$columnIndex['join_fallback']]) && !empty($row[$columnIndex['join_fallback']])) {
                $joinDateRaw = trim((string)$row[$columnIndex['join_fallback']]);
            } elseif (isset($columnIndex['join_date'], $row[$columnIndex['join_date']]) && !empty($row[$columnIndex['join_date']])) {
                $joinDateRaw = trim((string)$row[$columnIndex['join_date']]);
            }

            $mcuRaw = isset($columnIndex['mcu'], $row[$columnIndex['mcu']]) ? strtolower(trim((string)$row[$columnIndex['mcu']])) : 'no';
            $tldRaw = isset($columnIndex['tld'], $row[$columnIndex['tld']]) ? strtolower(trim((string)$row[$columnIndex['tld']])) : 'no';

            // Sanitisasi Nomor Rekening Bank
            $bankAccount = null;
            if ($bankAccountRaw !== null && $bankAccountRaw !== '') {
                $cleanedAcc = preg_replace('/[^0-9]/', '', trim($bankAccountRaw));
                if (!empty($cleanedAcc)) {
                    $bankAccount = $cleanedAcc;
                }
            }

            // Sanitisasi NIK
            $nik = null;
            if ($nikRaw !== null && $nikRaw !== '') {
                $cleanedNik = preg_replace('/[^0-9]/', '', trim($nikRaw));
                if (!empty($cleanedNik)) {
                    $nik = substr($cleanedNik, 0, 16);
                }
            }

            // Sanitisasi Salary
            $basicSalary = 0;
            if (!empty($salaryRaw)) {
                $cleanedSalary = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $salaryRaw));
                if (is_numeric($cleanedSalary)) {
                    $basicSalary = (float)$cleanedSalary;
                }
            }

            // Site Matching
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

            // Pencarian Karyawan Eksisting
            $employee = Employee::where('name', $nameRaw)->first();
            if (!$employee && $nik) {
                $employee = Employee::where('nik', $nik)->first();
            }

            // Sanitisasi Email
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

            // Parse Tanggal Bergabung
            $joinDateCarbon = null;
            if ($joinDateRaw) {
                try {
                    if (is_numeric($joinDateRaw)) {
                        $joinDateCarbon = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($joinDateRaw));
                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $joinDateRaw)) {
                        $joinDateCarbon = Carbon::parse($joinDateRaw);
                    } elseif (preg_match('/^\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{4}$/', $joinDateRaw)) {
                        $delimiter = str_contains($joinDateRaw, '/') ? '/' : (str_contains($joinDateRaw, '.') ? '.' : '-');
                        $joinDateCarbon = Carbon::createFromFormat("d{$delimiter}m{$delimiter}Y", $joinDateRaw);
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

            $payload = [
                'site_id'             => $matchedSite->id,
                'branch_id'           => $branchId,
                'name'                => $nameRaw,
                'bank_name'           => $bankNameRaw ?: null,
                'bank_account_number' => $bankAccount,
                'nik'                 => $nik,
                'phone_number'        => $phoneRaw,
                'email'               => $email,
                'basic_salary'        => $basicSalary,
                'position'            => is_numeric($positionRaw) ? null : $positionRaw,
                'status'              => $calculatedStatus,
                'mcu'                 => (str_contains($mcuRaw, 'yes') || str_contains($mcuRaw, 'ya') || str_contains($mcuRaw, 'done')) ? 'yes' : 'no',
                'tld'                 => (str_contains($tldRaw, 'yes') || str_contains($tldRaw, 'ya') || str_contains($tldRaw, 'need')) ? 'yes' : 'no',
                'join_date'           => $joinDateFormatted,
                'contract_start_date' => $contractStartDateFormatted,
                'is_active'           => true,
            ];

            if ($employee) {
                if (empty($payload['email']) && !empty($employee->email)) {
                    unset($payload['email']);
                }

                $oldSalary = $employee->basic_salary;
                $employee->update($payload);

                // Catat riwayat gaji jika ada kenaikan/perubahan dari Excel
                if ((float)$oldSalary !== (float)$basicSalary) {
                    EmployeeSalaryHistory::create([
                        'employee_id' => $employee->id,
                        'old_salary'  => $oldSalary ?? 0,
                        'new_salary'  => $basicSalary,
                        'reason'      => 'Penyesuaian Gaji via Import Excel',
                        'updated_by'  => $user->name ?? 'System Import',
                    ]);
                }
            } else {
                $newEmployee = Employee::create($payload);

                // Catat gaji awal untuk karyawan baru
                if ($basicSalary > 0) {
                    EmployeeSalaryHistory::create([
                        'employee_id' => $newEmployee->id,
                        'old_salary'  => 0,
                        'new_salary'  => $basicSalary,
                        'reason'      => 'Gaji Awal Karyawan Baru (Import Excel)',
                        'updated_by'  => $user->name ?? 'System Import',
                    ]);
                }
            }
        }
    }
}
