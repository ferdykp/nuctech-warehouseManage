<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Site;
use App\Models\Shift;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\IndonesianHolidayService;
use App\Exports\ScheduleExport;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function __construct(private IndonesianHolidayService $holidayService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $rawHolidays = $this->holidayService->getHolidaysForMonth(sprintf('%04d-%02d', $year, $month));

        // Standardisasi key agar bertipe string "Y-m-d"
        $holidays = [];
        if (!empty($rawHolidays)) {
            foreach ($rawHolidays as $key => $val) {
                $dateKey = ($key instanceof Carbon) ? $key->format('Y-m-d') : (string) $key;
                $holidays[$dateKey] = $val;
            }
        }

        // Cek apakah akun superadmin / administration
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        if ($isSuperAdmin) {
            $selectedSiteId = $request->input('site_id', 'all');
            $sites = Site::with('schedulePattern')->get();
        } else {
            // Selain superadmin, hanya tampilkan data milik site user yang login
            $selectedSiteId = $user->site_id;
            $sites = Site::where('id', $user->site_id)->with('schedulePattern')->get();
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $datesInMonth = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $datesInMonth[] = $date->copy();
        }

        $employeesQuery = Employee::with(['site', 'schedules' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('shift');
        }]);

        // Filter karyawan berdasarkan role & site terpilih
        if (!$isSuperAdmin) {
            $employeesQuery->where('site_id', $user->site_id);
        } elseif ($selectedSiteId !== 'all' && !empty($selectedSiteId)) {
            $employeesQuery->where('site_id', $selectedSiteId);
        }

        $employees = $employeesQuery->get();

        return view('schedule.index', compact('employees', 'datesInMonth', 'month', 'year', 'sites', 'selectedSiteId', 'holidays'));
    }

    public function updateSitePattern(Request $request, $siteId)
    {
        $user = Auth::user();

        $site = Site::findOrFail($siteId);
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        if (!$isSuperAdmin && (int) $user->site_id !== (int) $site->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pola site ini.');
        }

        $validated = $request->validate([
            'schedule_type' => 'required|in:office_hour,shift_rotation',
            'work_days'     => 'nullable|integer|min:1',
            'off_days'      => 'nullable|integer|min:1',
        ]);

        $site->schedulePattern()->updateOrCreate(
            ['site_id' => $site->id],
            [
                'schedule_type' => $validated['schedule_type'],
                'work_days'     => $validated['work_days'] ?? 6,
                'off_days'      => $validated['off_days'] ?? 2,
            ]
        );

        return redirect()->back()->with('success', "Pola kerja site \"{$site->machine_name}\" berhasil disimpan.");
    }

    public function generate(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        $rules = [
            'target_site_id' => 'required|exists:sites,id',
            'month'          => 'required',
            'year'           => 'required',
            'start_day'      => 'required|integer|min:1|max:31',
            'shift_duration' => 'nullable|integer|min:1',
            'employee_ids'   => 'required|array|min:1',
            'start_shift_id' => 'required',
            'active_shifts'  => 'required|array|min:1',
            'schedule_type'  => 'required|in:office_hour,shift_rotation',
            'work_days'      => 'nullable|integer|min:1',
            'off_days'       => 'nullable|integer|min:1',
        ];

        $request->validate($rules);

        $site = Site::findOrFail($request->input('target_site_id'));

        if (!$isSuperAdmin && (int) $user->site_id !== (int) $site->id) {
            abort(403, 'Anda tidak memiliki akses untuk site ini.');
        }

        $site->schedulePattern()->updateOrCreate(
            ['site_id' => $site->id],
            [
                'schedule_type' => $request->input('schedule_type'),
                'work_days'     => $request->input('work_days') ?? 6,
                'off_days'      => $request->input('off_days') ?? 2,
            ]
        );
        $pattern = $site->schedulePattern()->first();

        $month = $request->input('month');
        $year = $request->input('year');
        $startDay = intval($request->input('start_day'));
        $shiftDuration = intval($request->input('shift_duration', 1));
        $selectedEmployeeIds = $request->input('employee_ids');
        $startShiftId = $request->input('start_shift_id');
        $activeShiftIds = $request->input('active_shifts');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $shiftOff = Shift::where('is_off', true)->first();
        if (!$shiftOff) {
            return redirect()->back()->withErrors(['error' => 'Gagal! Master Shift Libur (OFF) belum ada.']);
        }

        $holidays = $this->holidayService->getHolidays((int) $year);

        $existingShiftIds = Shift::whereIn('id', $activeShiftIds)->pluck('id')->toArray();
        $shiftsPool = array_values(array_filter($activeShiftIds, function ($id) use ($existingShiftIds) {
            return in_array($id, $existingShiftIds);
        }));

        if (empty($shiftsPool)) {
            return redirect()->back()->withErrors(['error' => 'Gagal! Tidak ada shift aktif yang dipilih.']);
        }

        $startIndex = array_search($startShiftId, $shiftsPool);
        if ($startIndex !== false) {
            $allowedShifts = array_merge(array_slice($shiftsPool, $startIndex), array_slice($shiftsPool, 0, $startIndex));
        } else {
            $allowedShifts = $shiftsPool;
        }

        $employeesQuery = Employee::whereIn('id', $selectedEmployeeIds)->where('site_id', $site->id);

        if (!$isSuperAdmin) {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->get();

        if ($employees->isEmpty()) {
            return redirect()->back()->withErrors(['error' => 'Tidak ada karyawan valid ditemukan untuk site yang dipilih.']);
        }

        foreach ($employees as $employee) {
            if ($pattern->schedule_type === 'shift_rotation') {
                $workDays = $pattern->work_days ?? 6;
                $offDays = $pattern->off_days ?? 2;
                $cycleLength = $workDays + $offDays;

                $workDayCounter = 0;

                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $currentDayNum = $date->day;

                    if ($currentDayNum < $startDay) {
                        EmployeeSchedule::updateOrCreate(
                            ['employee_id' => $employee->id, 'date' => $date->format('Y-m-d')],
                            ['shift_id' => $shiftOff->id]
                        );
                        continue;
                    }

                    $dayOfCycleIndex = ($currentDayNum - $startDay) % $cycleLength;

                    if ($dayOfCycleIndex < $workDays) {
                        $shiftGroupIndex = intval(floor($workDayCounter / max(1, $shiftDuration)));
                        $shiftIndex = $shiftGroupIndex % count($allowedShifts);

                        $assignedShift = $allowedShifts[$shiftIndex];
                        $workDayCounter++;
                    } else {
                        $assignedShift = $shiftOff->id;
                    }

                    EmployeeSchedule::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->format('Y-m-d')],
                        ['shift_id' => $assignedShift]
                    );
                }
            } else { // office_hour
                for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                    $isHoliday = $date->isWeekend() || isset($holidays[$date->format('Y-m-d')]);
                    $assignedShift = $isHoliday ? $shiftOff->id : $allowedShifts[0];
                    EmployeeSchedule::updateOrCreate(
                        ['employee_id' => $employee->id, 'date' => $date->format('Y-m-d')],
                        ['shift_id' => $assignedShift]
                    );
                }
            }
        }

        return redirect()->back()->with(
            'success',
            "Sukses men-generate jadwal regu untuk {$employees->count()} karyawan di site \"{$site->machine_name}\"."
        );
    }

    public function updateSingle(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'shift_id'   => 'required|exists:shifts,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        if (!$isSuperAdmin && (int)$user->site_id !== (int)$employee->site_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $request->employee_id, 'date' => $request->date],
            ['shift_id'    => $request->shift_id]
        );

        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function clearSchedule(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        $request->validate([
            'site_id' => 'required',
            'month'   => 'required',
            'year'    => 'required',
        ]);

        $siteId = $request->site_id;
        $month = sprintf('%02d', $request->month);
        $year = $request->year;

        if (!$isSuperAdmin && (int)$user->site_id !== (int)$siteId) {
            return redirect()->back()->withErrors(['error' => 'Akses ditolak untuk site ini.']);
        }

        $startDate = "{$year}-{$month}-01";
        $endDate = Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

        $employeeQuery = Employee::query();
        if (!$isSuperAdmin) {
            $employeeQuery->where('site_id', $user->site_id);
        } elseif ($siteId !== 'all') {
            $employeeQuery->where('site_id', $siteId);
        }

        $employeeIds = $employeeQuery->pluck('id');

        EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->delete();

        return redirect()->back()->with('success', 'Berhasil menghapus/meriset seluruh jadwal untuk periode ini.');
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = in_array($user->role, ['superadmin', 'administration']);

        $month = sprintf('%02d', $request->input('month', date('m')));
        $year = $request->input('year', date('Y'));

        if ($isSuperAdmin) {
            $siteId = $request->input('site_id', 'all');
        } else {
            $siteId = $user->site_id;
        }

        $siteName = 'Semua_Site';
        if ($siteId !== 'all') {
            $site = Site::find($siteId);
            if ($site) {
                $siteName = str_replace(' ', '_', $site->machine_name);
            }
        }

        $fileName = 'Jadwal_Kerja_' . $siteName . '_' . $month . '_' . $year . '.xlsx';

        return Excel::download(new ScheduleExport($siteId, $month, $year), $fileName);
    }
}
