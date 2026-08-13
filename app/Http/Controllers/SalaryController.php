<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Salary;
use App\Services\IndonesianHolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    public function __construct(private IndonesianHolidayService $holidayService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Salary::with('employee.site.branch');

        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $monthPeriod = sprintf('%04d-%02d', $year, $month);

        if ($user->role === 'admin_site') {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->where('site_id', $user->site_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('account_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('information')) {
            $query->where('information', $request->information);
        }

        if ($request->filled('bank')) {
            $query->where('bank', $request->bank);
        }

        $salaries = $query->latest()->paginate(10)->appends($request->all());

        foreach ($salaries as $salary) {
            $calc = $this->calculateSalaryDetails($salary->employee_id, $monthPeriod);

            if ($calc['holiday_overtime_days'] > 0) {
                $salary->calculated_before_after = 'Rp ' . number_format($salary->amount, 0, ',', '.') .
                    ' / Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                    ' (+Lembur ' . $calc['holiday_overtime_days'] . ' Hr Tgl Merah)';
            } else {
                $salary->calculated_before_after = $salary->before_after ?? 'Rp ' . number_format($salary->amount, 0, ',', '.');
            }
        }

        // PERBAIKAN: Jika dipanggil via AJAX/Filter, hanya render bagian tabel
        if ($request->ajax() && !$request->wantsJson()) {
            return view('salary.table', compact('salaries', 'month', 'year'))->render();
        }

        $banks = Salary::select('bank')->distinct()->pluck('bank');

        return view('salary.index', compact('salaries', 'banks', 'month', 'year'));
    }

    public function create()
    {
        $user = Auth::user();
        $employeesQuery = Employee::with(['site.branch', 'branch']);

        if ($user->role === 'admin_site') {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->get();

        return view('salary.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'bank'        => 'required|string|max:100',
            'account_no'  => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0',
            'information' => 'required|in:1st probation,2nd probation,3rd probation,regular salary',
        ]);

        $employee = Employee::with(['site.branch', 'branch'])->findOrFail($request->employee_id);

        $placementName = $employee->branch->branch_name
            ?? ($employee->site->branch->branch_name ?? '-');

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

        return redirect()->route('salary.index')->with('success', 'Data gaji berhasil ditambahkan!');
    }

    public function show(Request $request, $id)
    {
        try {
            $salary = Salary::with('employee.site.branch')->findOrFail($id);

            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));
            $monthPeriod = sprintf('%04d-%02d', $year, $month);

            $calc = $this->calculateSalaryDetails($salary->employee_id, $monthPeriod);

            if ($calc['holiday_overtime_days'] > 0) {
                $salary->before_after = 'Rp ' . number_format($salary->amount, 0, ',', '.') .
                    ' / Rp ' . number_format($calc['total_salary_to_pay'], 0, ',', '.') .
                    ' (Lembur ' . $calc['holiday_overtime_days'] . ' Hari Tgl Merah)';
            } else {
                $salary->before_after = $salary->before_after ?? 'Rp ' . number_format($salary->amount, 0, ',', '.');
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
            return response()->json(['message' => 'Gagal mengambil detail gaji: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $salary = Salary::findOrFail($id);
        $user = Auth::user();

        $employeesQuery = Employee::with(['site.branch', 'branch']);
        if ($user->role === 'admin_site') {
            $employeesQuery->where('site_id', $user->site_id);
        }
        $employees = $employeesQuery->get();

        return view('salary.edit', compact('salary', 'employees'));
    }

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

        $employee = Employee::with(['site.branch', 'branch'])->findOrFail($request->employee_id);

        $placementName = $employee->branch->branch_name
            ?? ($employee->site->branch->branch_name ?? '-');

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

        return redirect()->route('salary.index')->with('success', 'Data gaji berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $salary = Salary::findOrFail($id);
        $salary->delete();

        return redirect()->route('salary.index')->with('success', 'Data gaji berhasil dihapus!');
    }

    private function calculateSalaryDetails($employeeId, $monthPeriod = null)
    {
        $monthPeriod = $monthPeriod ?? date('Y-m');
        $startOfMonth = Carbon::parse($monthPeriod . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($monthPeriod . '-01')->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $nationalHolidays = $this->holidayService->getHolidaysForMonth($monthPeriod);

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
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->with('shift')
            ->get();

        $regularWorkDays = 0;
        $holidayOvertimeDays = 0;
        $holidayDates = [];

        foreach ($schedules as $sched) {
            if ($sched->shift && !$sched->shift->is_off) {
                $dateStr = $sched->date;
                $isNationalHoliday = isset($nationalHolidays[$dateStr]);

                if ($isNationalHoliday) {
                    $holidayOvertimeDays++;
                    $holidayDates[] = [
                        'date' => $dateStr,
                        'name' => $nationalHolidays[$dateStr]
                    ];
                } else {
                    $regularWorkDays++;
                }
            }
        }

        $salaryMaster = Salary::where('employee_id', $employeeId)->first();
        $monthlySalary = $salaryMaster ? $salaryMaster->amount : 0;

        $dailyRate = $effectiveWorkingDays > 0 ? ($monthlySalary / $effectiveWorkingDays) : 0;

        // PERBAIKAN: Gaji pokok bulanan tetap penuh (monthlySalary)
        // Pemotongan prorata hanya terjadi jika kehadiran hari reguler berkurang (mangkir/absen)
        $baseCalculatedSalary = ($effectiveWorkingDays > 0 && $regularWorkDays < $effectiveWorkingDays)
            ? ($regularWorkDays / $effectiveWorkingDays) * $monthlySalary
            : $monthlySalary;

        // Uang lembur murni ditambahkan di atas gaji pokok
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
            'total_salary_to_pay'    => $totalSalaryToPay
        ];
    }
}
