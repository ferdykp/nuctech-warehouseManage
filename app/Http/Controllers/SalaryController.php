<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Salary;
use App\Services\IndonesianHolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\SalaryExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Branch; // <-- Tambahkan Import ini



class SalaryController extends Controller
{
    public function __construct(
        private IndonesianHolidayService $holidayService
    ) {}

    /**
     * ============================================================
     * INDEX
     * ============================================================
     */

    // ... di dalam class SalaryController ...

    public function index(Request $request)
    {
        $user = Auth::user();

        $month = sprintf('%02d', $request->input('month', date('m')));
        $year = (int) $request->input('year', date('Y'));
        $monthPeriod = "{$year}-{$month}";

        $startDate = Carbon::createFromDate($year, (int) $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, (int) $month, 1)->endOfMonth();

        $query = Salary::with([
            'employee.site.branch',
            'employee.branch',
        ])
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
            ]);

        if ($user->role === 'employee_role') {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('account_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('information')) {
            $query->where('information', $request->input('information'));
        }

        if ($request->filled('bank')) {
            $query->where('bank', $request->input('bank'));
        }

        // PERBAIKAN: Filter berdasarkan Branch (Cabang)
        if ($request->filled('branch_id')) {
            $branchId = $request->input('branch_id');
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('employee.branch', function ($b) use ($branchId) {
                    $b->where('id', $branchId);
                })->orWhereHas('employee.site.branch', function ($b) use ($branchId) {
                    $b->where('id', $branchId);
                });
            });
        }

        $salaries = $query->latest('created_at')->paginate(10)->appends($request->all());

        foreach ($salaries as $salary) {
            $calc = $this->calculateSalaryDetails($salary->employee_id, $monthPeriod);

            if ($calc['holiday_overtime_days'] > 0) {
                $salary->calculated_before_after =
                    'Rp ' . number_format($salary->amount, 0, ',', '.') .
                    ' / Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                    ' (+Lembur ' . $calc['holiday_overtime_days'] . ' Hr Tgl Merah)';
            } else {
                $salary->calculated_before_after =
                    $salary->before_after ??
                    'Rp ' . number_format($salary->amount, 0, ',', '.');
            }
        }

        if ($request->ajax() && !$request->wantsJson()) {
            return view('salary.table', compact('salaries', 'month', 'year'))->render();
        }

        $banks = Salary::select('bank')
            ->whereNotNull('bank')
            ->where('bank', '!=', '')
            ->distinct()
            ->orderBy('bank')
            ->pluck('bank');

        // PERBAIKAN: Ambil daftar Branch untuk dikirim ke view
        $branches = Branch::orderBy('branch_name')->get();

        return view(
            'salary.index',
            compact('salaries', 'banks', 'branches', 'month', 'year')
        );
    }
    /**
     * ============================================================
     * CREATE
     * ============================================================
     */
    public function create()
    {
        $user = Auth::user();

        $employeesQuery = Employee::with([
            'site.branch',
            'branch',
        ]);

        if ($user->role === 'employee_role') {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->get();

        return view('salary.create', compact('employees'));
    }

    /**
     * ============================================================
     * STORE MANUAL SALARY
     * ============================================================
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bank'        => 'required|string|max:100',
            'account_no'  => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0',
            'information' => 'required|in:1st probation,2nd probation,3rd probation,regular salary',
        ]);

        $employee = Employee::with([
            'site.branch',
            'branch',
        ])->findOrFail($request->employee_id);

        $placementName =
            $employee->branch->branch_name
            ?? $employee->site->branch->branch_name
            ?? '-';

        Salary::create([
            'employee_id'      => $employee->id,
            'name'             => $employee->name,
            'position'         => $employee->position ?? '-',
            'placement'        => $placementName,
            'bank'             => $request->bank,
            'account_no'       => $request->account_no,
            'amount'           => $request->amount,
            'information'      => $request->information,
            'before_after'     => $request->before_after,
            'more_information' => $request->more_information,
            'get_information'  => $request->get_information,
        ]);

        return redirect()
            ->route('salary.index')
            ->with('success', 'Data gaji berhasil ditambahkan!');
    }

    /**
     * ============================================================
     * SHOW DETAIL GAJI (POP-UP MODAL)
     * ============================================================
     */
    public function show(Request $request, $id)
    {
        try {
            $salary = Salary::with([
                'employee.site.branch',
                'employee.branch',
            ])->findOrFail($id);

            $month = sprintf('%02d', $request->input('month', date('m')));
            $year = (int) $request->input('year', date('Y'));
            $monthPeriod = "{$year}-{$month}";

            $calc = $this->calculateSalaryDetails(
                $salary->employee_id,
                $monthPeriod
            );

            if ($calc['holiday_overtime_days'] > 0) {
                $salary->before_after =
                    'Rp ' . number_format($salary->amount, 0, ',', '.') .
                    ' / Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                    ' (Lembur ' . $calc['holiday_overtime_days'] . ' Hari Tgl Merah)';
            } else {
                $salary->before_after =
                    $salary->before_after ??
                    'Rp ' . number_format($salary->amount, 0, ',', '.');
            }

            $salary->amount_formatted = 'Rp ' . number_format($salary->amount, 0, ',', '.');
            $salary->calculation = [
                'effective_days'        => $calc['effective_working_days'],
                'total_attended'        => $calc['total_attended_days'],
                'holiday_overtime_days' => $calc['holiday_overtime_days'],
                'holiday_dates'         => $calc['holiday_dates'],
                'holiday_overtime_pay'  => 'Rp ' . number_format($calc['holiday_overtime_pay'], 0, ',', '.'),
                'total_salary_to_pay'   => 'Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.'),
            ];

            return response()->json($salary);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil detail gaji: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ============================================================
     * EDIT
     * ============================================================
     */
    public function edit($id)
    {
        $salary = Salary::findOrFail($id);
        $user = Auth::user();

        $employeesQuery = Employee::with([
            'site.branch',
            'branch',
        ]);

        if ($user->role === 'employee_role') {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->get();

        return view('salary.edit', compact('salary', 'employees'));
    }

    /**
     * ============================================================
     * UPDATE
     * ============================================================
     */
    public function update(Request $request, $id)
    {
        $salary = Salary::findOrFail($id);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bank'        => 'required|string|max:100',
            'account_no'  => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0',
            'information' => 'required|in:1st probation,2nd probation,3rd probation,regular salary',
        ]);

        $employee = Employee::with([
            'site.branch',
            'branch',
        ])->findOrFail($request->employee_id);

        $placementName =
            $employee->branch->branch_name
            ?? $employee->site->branch->branch_name
            ?? '-';

        $salary->update([
            'employee_id'      => $employee->id,
            'name'             => $employee->name,
            'position'         => $employee->position ?? '-',
            'placement'        => $placementName,
            'bank'             => $request->bank,
            'account_no'       => $request->account_no,
            'amount'           => $request->amount,
            'information'      => $request->information,
            'before_after'     => $request->before_after,
            'more_information' => $request->more_information,
            'get_information'  => $request->get_information,
        ]);

        return redirect()
            ->route('salary.index')
            ->with('success', 'Data gaji berhasil diperbarui!');
    }

    /**
     * ============================================================
     * DESTROY
     * ============================================================
     */
    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete();

        return redirect()
            ->route('salary.index')
            ->with('success', 'Data gaji berhasil dihapus!');
    }

    /**
     * ============================================================
     * CALCULATE SALARY DETAILS
     * ============================================================
     */
    /**
     * ============================================================
     * CALCULATE SALARY DETAILS
     * ============================================================
     */
    private function calculateSalaryDetails($employeeId, $monthPeriod = null)
    {
        $monthPeriod = $monthPeriod ?? date('Y-m');

        $startOfMonth = Carbon::parse($monthPeriod . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod . '-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $rawHolidays = $this->holidayService->getHolidaysForMonth($monthPeriod);

        // Standardisasi $nationalHolidays agar KEY-nya bertipe STRING ("Y-m-d")
        $nationalHolidays = [];
        if (!empty($rawHolidays)) {
            foreach ($rawHolidays as $key => $val) {
                // Jika key adalah Objek Carbon
                if ($key instanceof Carbon) {
                    $dateKey = $key->format('Y-m-d');
                } else {
                    $dateKey = (string) $key;
                }
                $nationalHolidays[$dateKey] = $val;
            }
        }

        $effectiveWorkingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::parse(sprintf('%s-%02d', $monthPeriod, $d));
            $dateStr = $date->format('Y-m-d');

            $isWeekend = $date->isWeekend();
            $isNationalHoliday = isset($nationalHolidays[$dateStr]);

            if (!$isWeekend && !$isNationalHoliday) {
                $effectiveWorkingDays++;
            }
        }

        $schedules = EmployeeSchedule::where('employee_id', $employeeId)
            ->whereBetween('date', [
                $startOfMonth->format('Y-m-d'),
                $endOfMonth->format('Y-m-d'),
            ])
            ->with('shift')
            ->get();

        $regularWorkDays = 0;
        $holidayOvertimeDays = 0;
        $holidayDates = [];

        foreach ($schedules as $sched) {
            if ($sched->shift && !$sched->shift->is_off) {
                // Pastikan $dateStr bertipe string "Y-m-d"
                $dateStr = $sched->date instanceof Carbon
                    ? $sched->date->format('Y-m-d')
                    : (string) $sched->date;

                $isNationalHoliday = isset($nationalHolidays[$dateStr]);

                if ($isNationalHoliday) {
                    $holidayOvertimeDays++;
                    $holidayDates[] = [
                        'date' => $dateStr,
                        'name' => $nationalHolidays[$dateStr],
                    ];
                } else {
                    $regularWorkDays++;
                }
            }
        }

        $salaryMaster = Salary::where('employee_id', $employeeId)
            ->orderByDesc('created_at')
            ->first();

        $monthlySalary = $salaryMaster ? $salaryMaster->amount : 0;

        $dailyRate = $effectiveWorkingDays > 0 ? ($monthlySalary / $effectiveWorkingDays) : 0;

        $baseCalculatedSalary = ($effectiveWorkingDays > 0 && $regularWorkDays < $effectiveWorkingDays)
            ? ($regularWorkDays / $effectiveWorkingDays) * $monthlySalary
            : $monthlySalary;

        $holidayOvertimePay = $holidayOvertimeDays * $dailyRate;
        $totalSalaryToPay = $monthlySalary + $holidayOvertimePay;

        return [
            'monthly_salary'         => $monthlySalary,
            'effective_working_days' => $effectiveWorkingDays,
            'total_attended_days'    => $regularWorkDays + $holidayOvertimeDays,
            'regular_work_days'      => $regularWorkDays,
            'holiday_overtime_days'  => $holidayOvertimeDays,
            'holiday_dates'          => $holidayDates,
            'daily_rate'             => $dailyRate,
            'base_calculated_salary' => $baseCalculatedSalary,
            'holiday_overtime_pay'   => $holidayOvertimePay,
            'total_salary_to_pay'    => $totalSalaryToPay,
        ];
    }
    /**
     * ============================================================
     * GENERATE MONTHLY SALARIES
     * ============================================================
     */
    /**
     * ============================================================
     * GENERATE MONTHLY SALARIES
     * ============================================================
     */
    public function generateMonthlySalaries(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Validasi Input Month & Year dari Modal
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'month' => 'required',
            'year'  => 'required|integer|min:2000|max:2100',
        ]);

        $month = sprintf('%02d', (int) $request->input('month'));
        $year = (int) $request->input('year');
        $period = "{$year}-{$month}";

        /*
        |--------------------------------------------------------------------------
        | Rentang Bulan Target
        |--------------------------------------------------------------------------
        */
        $startOfMonth = Carbon::createFromDate($year, (int) $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, (int) $month, 1)->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | Timestamp Khusus Record Payroll
        |--------------------------------------------------------------------------
        | Patokan tanggal 15 jam 12:00:00 pada bulan & tahun target.
        */
        $targetTimestamp = Carbon::create($year, (int) $month, 15, 12, 0, 0);

        /*
        |--------------------------------------------------------------------------
        | Query Data Karyawan Aktif
        |--------------------------------------------------------------------------
        */
        $query = Employee::where('is_active', true)->with([
            'site.branch',
            'branch',
        ]);

        if ($user->role === 'employee_role') {
            $query->where('site_id', $user->site_id);
        }

        $employees = $query->get();

        $processedCount = 0;
        $createdCount = 0;
        $updatedCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Pemrosesan Generate / Update Massal
        |--------------------------------------------------------------------------
        */
        foreach ($employees as $emp) {
            $placementName =
                $emp->branch->branch_name
                ?? $emp->site->branch->branch_name
                ?? '-';

            $calculatedInfo = $emp->getCalculatedSalaryInformation($period);

            /*
            |--------------------------------------------------------------------------
            | Logika Pengisian More Information (Lembur Tanggal Merah)
            |--------------------------------------------------------------------------
            */
            $calc = $this->calculateSalaryDetails($emp->id, $period);
            $moreInformation = null;

            if (!empty($calc['holiday_dates']) && count($calc['holiday_dates']) > 0) {
                $holidayTexts = [];
                foreach ($calc['holiday_dates'] as $hd) {
                    $formattedDate = Carbon::parse($hd['date'])->translatedFormat('d F Y');
                    $holidayTexts[] = "• {$formattedDate} ({$hd['name']})";
                }

                $moreInformation = "Masuk Kerja Tanggal Merah:\n" . implode("\n", $holidayTexts);
            }

            // Cari apakah gaji karyawan ini sudah terdaftar pada bulan & tahun target
            $existingSalary = Salary::where('employee_id', $emp->id)
                ->whereBetween('created_at', [
                    $startOfMonth->format('Y-m-d H:i:s'),
                    $endOfMonth->format('Y-m-d H:i:s'),
                ])
                ->first();

            if ($existingSalary) {
                /*
                |--------------------------------------------------------------------------
                | UPDATE DATA LAMA
                |--------------------------------------------------------------------------
                */
                $existingSalary->employee_id      = $emp->id;
                $existingSalary->name             = $emp->name;
                $existingSalary->position         = $emp->position ?? '-';
                $existingSalary->placement        = $placementName;
                $existingSalary->bank             = $emp->bank_name ?? 'BCA';
                $existingSalary->account_no       = $emp->bank_account_number ?? '-';
                $existingSalary->amount           = $emp->basic_salary ?? 0;
                $existingSalary->information      = $calculatedInfo;
                $existingSalary->more_information = $moreInformation;
                $existingSalary->updated_at       = now();

                $existingSalary->save();
                $updatedCount++;
            } else {
                /*
                |--------------------------------------------------------------------------
                | CREATE NEW DATA SALARY
                |--------------------------------------------------------------------------
                */
                $newSalary = new Salary();
                $newSalary->timestamps = false; // Mematikan penimpaan waktu otomatis Laravel

                $newSalary->employee_id      = $emp->id;
                $newSalary->name             = $emp->name;
                $newSalary->position         = $emp->position ?? '-';
                $newSalary->placement        = $placementName;
                $newSalary->bank             = $emp->bank_name ?? 'BCA';
                $newSalary->account_no       = $emp->bank_account_number ?? '-';
                $newSalary->amount           = $emp->basic_salary ?? 0;
                $newSalary->information      = $calculatedInfo;
                $newSalary->more_information = $moreInformation;

                $newSalary->created_at = $targetTimestamp;
                $newSalary->updated_at = $targetTimestamp;

                $newSalary->save();
                $createdCount++;
            }

            $processedCount++;
        }

        /*
        |--------------------------------------------------------------------------
        | Redirect Kembali Ke Halaman Index Dengan Parameter Filter Bulan & Tahun
        |--------------------------------------------------------------------------
        */
        return redirect()
            ->route('salary.index', [
                'month' => $month,
                'year'  => $year,
            ])
            ->with(
                'success',
                "Sukses memproses data gaji untuk {$processedCount} karyawan pada periode {$month}/{$year}. " .
                    "Dibuat: {$createdCount}, diperbarui: {$updatedCount}."
            );
    }


// ... di dalam class SalaryController

    /**
     * ============================================================
     * EXPORT EXCEL
     * ============================================================
     */
    public function exportExcel(Request $request)
    {
        $month = sprintf('%02d', (int) $request->input('month', date('m')));
        $year = (int) $request->input('year', date('Y'));
        $search = $request->input('search');
        $information = $request->input('information');
        $bank = $request->input('bank');
        $user = Auth::user();

        $fileName = "Data_Gaji_Karyawan_{$month}_{$year}.xlsx";

        return Excel::download(
            new SalaryExport($month, $year, $search, $information, $bank, $user),
            $fileName
        );
    }
}
