<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeesImport implements ToCollection
{
    protected ?int $siteId;

    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;

    private array $aliases = [
        'id' => ['employee id', 'employee_id'],
        'name' => ['name', 'nama', 'nama karyawan'],
        'nik' => ['nik', 'no nik', 'nomor nik'],
        'email' => ['email', 'e-mail'],
        'phone_number' => ['phone', 'phone number', 'phone_number', 'no hp', 'telp', 'nomor telepon'],
        'bank_name' => ['bank name', 'bank_name', 'nama bank'],
        'bank_account_number' => ['bank account', 'bank account number', 'bank_account_number', 'nomor rekening'],
        'basic_salary' => ['salary', 'basic salary', 'basic_salary', 'gaji', 'gaji pokok'],
        'position' => ['position', 'jabatan'],
        'site' => ['site', 'lokasi mesin'],
        'designation' => ['designation', 'branch', 'cabang'],
        'status' => ['status', 'status kepegawaian'],
        'join_date' => ['join date', 'join_date', 'tanggal bergabung', 'join'],
        'contract_start_date' => ['contract start date', 'contract_start_date', 'tanggal mulai kontrak'],
        'pkwt_date' => ['pkwt date', 'pkwt_date', 'tanggal pkwt'],
        'pkwt_number' => ['pkwt number', 'pkwt_number', 'nomor pkwt', 'no pkwt', 'no. pkwt', 'number for pkwt'],
        'mcu' => ['mcu'],
        'tld' => ['tld'],
    ];

    public function __construct($siteId = null)
    {
        $this->siteId = $siteId ? (int) $siteId : null;
    }

    private function normalizeHeader($value): string
    {
        return preg_replace(
            '/\s+/',
            ' ',
            str_replace(['_', '-', '.'], ' ', mb_strtolower(trim((string) $value)))
        );
    }

    private function normalizeName($value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $value)));
    }

    private function filledValue($value): bool
    {
        return $value !== null
            && trim((string) $value) !== ''
            && trim((string) $value) !== '-';
    }

    private function mapHeaders(Collection $row): array
    {
        $map = [];

        foreach ($row as $index => $value) {
            $header = $this->normalizeHeader($value);

            foreach ($this->aliases as $field => $names) {
                foreach ($names as $alias) {
                    if ($header === $this->normalizeHeader($alias)) {
                        if (isset($map[$field])) {
                            throw new \RuntimeException(
                                "Header {$field} ditemukan lebih dari sekali."
                            );
                        }

                        $map[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    private function value(Collection $row, array $map, string $field)
    {
        if (!array_key_exists($field, $map)) {
            return null;
        }

        return $row->get($map[$field]);
    }

    private function parseDate($value, int $rowNumber, string $field): string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->format('Y-m-d');
            }

            $raw = trim((string) $value);

            foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y', '!d.m.Y'] as $format) {
                $date = Carbon::createFromFormat($format, $raw);

                if ($date && $date->format(substr($format, 1)) === $raw) {
                    return $date->format('Y-m-d');
                }
            }
        } catch (\Throwable $e) {
            // Ditangani melalui exception di bawah.
        }

        throw new \RuntimeException(
            "Baris {$rowNumber}: format {$field} tidak valid. Gunakan YYYY-MM-DD."
        );
    }

    private function findEmployee(
        Collection $row,
        array $map,
        int $rowNumber
    ): ?Employee {
        $rawId = $this->value($row, $map, 'id');
        $rawNik = $this->value($row, $map, 'nik');
        $rawName = $this->value($row, $map, 'name');

        $name = $this->filledValue($rawName)
            ? $this->normalizeName($rawName)
            : null;

        $nik = $this->filledValue($rawNik)
            ? preg_replace('/\D/', '', (string) $rawNik)
            : null;

        $employee = null;

        if ($this->filledValue($rawId)) {
            if (!ctype_digit((string) $rawId)) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: Employee ID tidak valid."
                );
            }

            $employee = Employee::find((int) $rawId);

            if (!$employee) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: Employee ID tidak ditemukan."
                );
            }
        } elseif ($nik) {
            $matches = Employee::where('nik', $nik)->limit(2)->get();

            if ($matches->count() > 1) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: NIK digunakan oleh lebih dari satu karyawan."
                );
            }

            $employee = $matches->first();
        }

        if ($employee) {
            if ($nik && (string) $employee->nik !== $nik) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: NIK berbeda dengan data Employee ID."
                );
            }

            if ($name && $this->normalizeName($employee->name) !== $name) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: Nama tidak cocok dengan identitas karyawan."
                );
            }

            return $employee;
        }

        // Jangan fallback ke nama apabila NIK terisi tetapi tidak cocok.
        if ($nik) {
            $sameName = $name
                ? Employee::whereRaw('LOWER(TRIM(name)) = ?', [$name])->exists()
                : false;

            if ($sameName) {
                throw new \RuntimeException(
                    "Baris {$rowNumber}: Nama ditemukan tetapi NIK berbeda."
                );
            }

            return null;
        }

        if (!$name) {
            throw new \RuntimeException(
                "Baris {$rowNumber}: Isi Employee ID, NIK, atau Name."
            );
        }

        $matches = Employee::whereRaw(
            'LOWER(TRIM(name)) = ?',
            [$name]
        )->limit(2)->get();

        if ($matches->count() > 1) {
            throw new \RuntimeException(
                "Baris {$rowNumber}: Nama karyawan tidak unik. Gunakan Employee ID atau NIK."
            );
        }

        return $matches->first();
    }

    private function resolveSite(
        string $siteName,
        ?string $designation,
        int $rowNumber
    ): Site {
        $clean = fn($value) => preg_replace(
            '/[^a-z0-9]/',
            '',
            mb_strtolower((string) $value)
        );

        $sites = Site::with('branch')->get();

        $matches = $sites->filter(function ($site) use ($siteName, $designation, $clean) {
            $machine = $clean($site->machine_name);

            if ($machine !== $clean($siteName)) {
                return false;
            }

            if ($designation && $site->branch) {
                return $clean($site->branch->branch_name) === $clean($designation);
            }

            return true;
        });

        if ($matches->count() !== 1) {
            throw new \RuntimeException(
                "Baris {$rowNumber}: Site tidak ditemukan atau ambigu: {$siteName}."
            );
        }

        return $matches->first();
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            throw new \RuntimeException('Excel kosong.');
        }

        $headerIndex = null;
        $map = [];

        foreach ($rows->take(20) as $index => $row) {
            $candidate = $this->mapHeaders($row);

            if (
                isset($candidate['name'])
                || isset($candidate['id'])
                || isset($candidate['nik'])
            ) {
                $headerIndex = $index;
                $map = $candidate;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException(
                'Header Employee ID, NIK, atau Name tidak ditemukan.'
            );
        }

        $dataFields = array_diff(
            array_keys($map),
            ['id', 'name', 'nik', 'designation']
        );

        if (count($dataFields) === 0) {
            throw new \RuntimeException(
                'Tidak ada kolom data yang dapat diimport.'
            );
        }

        $user = Auth::user();

        DB::transaction(function () use ($rows, $headerIndex, $map, $user) {
            $processed = [];

            foreach ($rows as $index => $row) {
                if ($index <= $headerIndex) {
                    continue;
                }

                $rowNumber = $index + 1;

                if ($row->filter(fn($v) => $this->filledValue($v))->isEmpty()) {
                    continue;
                }

                $employee = $this->findEmployee($row, $map, $rowNumber);

                if ($employee) {
                    if (
                        $user?->role === 'employee_role'
                        && (int) $employee->site_id !== (int) $user->site_id
                    ) {
                        throw new \RuntimeException(
                            "Baris {$rowNumber}: Anda tidak memiliki akses ke karyawan ini."
                        );
                    }

                    if (in_array($employee->id, $processed, true)) {
                        throw new \RuntimeException(
                            "Baris {$rowNumber}: Karyawan muncul lebih dari sekali."
                        );
                    }

                    $processed[] = $employee->id;
                }

                $payload = [];

                $simpleFields = [
                    'name',
                    'phone_number',
                    'bank_name',
                    'bank_account_number',
                    'position',
                    'pkwt_number',
                ];

                foreach ($simpleFields as $field) {
                    if (!array_key_exists($field, $map)) {
                        continue;
                    }

                    $value = $this->value($row, $map, $field);

                    if ($this->filledValue($value)) {
                        $payload[$field] = trim((string) $value);
                    }
                }

                if (isset($map['nik'])) {
                    $value = $this->value($row, $map, 'nik');

                    if ($this->filledValue($value)) {
                        $nik = preg_replace('/\D/', '', (string) $value);

                        if (strlen($nik) !== 16) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: NIK harus 16 digit."
                            );
                        }

                        $payload['nik'] = $nik;
                    }
                }

                if (isset($map['email'])) {
                    $email = $this->value($row, $map, 'email');

                    if ($this->filledValue($email)) {
                        $email = mb_strtolower(trim((string) $email));

                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: Email tidak valid."
                            );
                        }

                        $exists = Employee::where('email', $email)
                            ->when($employee, fn($q) => $q->where('id', '!=', $employee->id))
                            ->exists();

                        if ($exists) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: Email sudah digunakan."
                            );
                        }

                        $payload['email'] = $email;
                    }
                }

                foreach (
                    ['join_date', 'contract_start_date', 'pkwt_date'] as $field
                ) {
                    if (!isset($map[$field])) {
                        continue;
                    }

                    $value = $this->value($row, $map, $field);

                    if ($this->filledValue($value)) {
                        $payload[$field] = $this->parseDate(
                            $value,
                            $rowNumber,
                            $field
                        );
                    }
                }

                foreach (['mcu', 'tld'] as $field) {
                    if (!isset($map[$field])) {
                        continue;
                    }

                    $value = $this->value($row, $map, $field);

                    if (!$this->filledValue($value)) {
                        continue;
                    }

                    $normalized = mb_strtolower(trim((string) $value));

                    if (in_array($normalized, ['yes', 'ya', 'done', 'need', '1'], true)) {
                        $payload[$field] = 'yes';
                    } elseif (in_array($normalized, ['no', 'tidak', '0'], true)) {
                        $payload[$field] = 'no';
                    } else {
                        throw new \RuntimeException(
                            "Baris {$rowNumber}: Nilai {$field} tidak valid."
                        );
                    }
                }

                if (isset($map['status'])) {
                    $status = $this->value($row, $map, 'status');

                    if ($this->filledValue($status)) {
                        $allowed = [
                            'Permanent',
                            'Contract',
                            'Probation',
                            'Daily',
                            'Resigned',
                        ];

                        $matched = collect($allowed)->first(
                            fn($item) => strcasecmp($item, trim((string) $status)) === 0
                        );

                        if (!$matched) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: Status tidak valid."
                            );
                        }

                        $payload['status'] = $matched;
                        $payload['is_active'] = $matched !== 'Resigned';
                    }
                }

                if (isset($map['basic_salary'])) {
                    $salary = $this->value($row, $map, 'basic_salary');

                    if ($this->filledValue($salary)) {
                        if (!is_numeric($salary) || (float) $salary < 0) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: Gaji harus berupa angka non-negatif."
                            );
                        }

                        $payload['basic_salary'] = (float) $salary;
                    }
                }

                if (isset($map['site'])) {
                    $siteValue = $this->value($row, $map, 'site');

                    if ($this->filledValue($siteValue)) {
                        $designation = $this->value($row, $map, 'designation');

                        $site = $this->resolveSite(
                            trim((string) $siteValue),
                            $this->filledValue($designation)
                                ? trim((string) $designation)
                                : null,
                            $rowNumber
                        );

                        if (
                            $user?->role === 'employee_role'
                            && (int) $site->id !== (int) $user->site_id
                        ) {
                            throw new \RuntimeException(
                                "Baris {$rowNumber}: Site berada di luar hak akses."
                            );
                        }

                        $payload['site_id'] = $site->id;
                        $payload['branch_id'] = $site->branch_id;
                    }
                }

                if ($employee) {
                    // Nama, NIK dan identitas yang terisi juga divalidasi;
                    // hanya atribut yang benar-benar berubah yang disimpan.
                    $oldSalary = $employee->basic_salary;

                    $employee->fill($payload);

                    if (!$employee->isDirty()) {
                        $this->unchanged++;
                        continue;
                    }

                    $salaryChanged = $employee->isDirty('basic_salary');

                    $employee->save();

                    if ($salaryChanged) {
                        EmployeeSalaryHistory::create([
                            'employee_id' => $employee->id,
                            'old_salary' => $oldSalary ?? 0,
                            'new_salary' => $employee->basic_salary,
                            'reason' => 'Penyesuaian Gaji via Import Excel',
                            'updated_by' => $user?->name ?? 'System Import',
                        ]);
                    }

                    $this->updated++;
                    continue;
                }

                // Pembuatan karyawan baru memerlukan data lengkap.
                if (!isset($payload['name'], $payload['phone_number'])) {
                    throw new \RuntimeException(
                        "Baris {$rowNumber}: Karyawan tidak ditemukan. Import parsial tidak boleh membuat karyawan baru."
                    );
                }

                if (!isset($payload['site_id'])) {
                    if (!$this->siteId) {
                        throw new \RuntimeException(
                            "Baris {$rowNumber}: Karyawan baru memerlukan Site."
                        );
                    }

                    $site = Site::findOrFail($this->siteId);
                    $payload['site_id'] = $site->id;
                    $payload['branch_id'] = $site->branch_id;
                }

                $payload['status'] = $payload['status'] ?? 'Probation';
                $payload['is_active'] = $payload['status'] !== 'Resigned';

                $newEmployee = Employee::create($payload);

                if (($newEmployee->basic_salary ?? 0) > 0) {
                    EmployeeSalaryHistory::create([
                        'employee_id' => $newEmployee->id,
                        'old_salary' => 0,
                        'new_salary' => $newEmployee->basic_salary,
                        'reason' => 'Gaji Awal Karyawan Baru (Import Excel)',
                        'updated_by' => $user?->name ?? 'System Import',
                    ]);
                }

                $this->created++;
            }
        });
    }
}
