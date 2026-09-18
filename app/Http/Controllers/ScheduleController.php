<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule as Schedule;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SiteSchedule as SitePattern;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $month = sprintf('%02d', $request->get('month', date('m')));
        $year = $request->get('year', date('Y'));
        $selectedSiteId = $request->get('site_id', 'all');

        // Periode Tanggal
        $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $datesInMonth = CarbonPeriod::create($startDate, $endDate);

        // Load semua site
        $sitesQuery = Site::with('schedulePattern')->orderBy('machine_name', 'asc');

        if ($user && $user->role === 'team_leader' && $user->site) {
            // 1. Ambil kata depan sebelum spasi (misal: "CTMIC2100YW" atau "CTMIC2100-YW")
            $firstName = explode(' ', trim($user->site->machine_name))[0];

            // 2. Bersihkan tanda strip '-' agar tersisa kata utamanya saja (misal: "CTMIC2100")
            $cleanPrefix = str_replace('-', '', $firstName);

            // 3. Ambil 5 karakter dasar ("CTMIC") sebagai kunci utama
            $baseKey = substr($cleanPrefix, 0, 5);

            // Filter site yang mengandung kata kunci "CTMIC"
            $sitesQuery->where('machine_name', 'LIKE', '%' . $baseKey . '%');
        }

        $sites = $sitesQuery->get();
        $allowedSiteIds = $sites->pluck('id')->toArray();

        // Filter query karyawan
        $employeeQuery = Employee::with('site');

        // Filter Karyawan Resign: Hanya tampilkan jika aktif ATAU resign_date masih jatuh pada/setelah awal bulan ini
        $employeeQuery->where(function ($q) use ($startDate) {
            $q->whereNull('resign_date')
                ->orWhere('resign_date', '>=', $startDate->format('Y-m-d'));
        });

        if ($user && $user->role === 'team_leader') {
            // Team Leader melihat semua karyawan di kelompok site project-nya
            $employeeQuery->whereIn('site_id', $allowedSiteIds);
        } elseif ($selectedSiteId !== 'all' && !empty($selectedSiteId)) {
            if (is_array($selectedSiteId)) {
                $employeeQuery->whereIn('site_id', $selectedSiteId);
            } else {
                $employeeQuery->where('site_id', $selectedSiteId);
            }
        }

        $employees = $employeeQuery->orderBy('name', 'asc')->get();

        // Schedule Logs
        $employeeIds = $employees->pluck('id');
        $schedules = Schedule::with('shift')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $schedulesByEmployee = $schedules->groupBy('employee_id');

        foreach ($employees as $emp) {
            $empSchedules = $schedulesByEmployee->get($emp->id);
            $emp->setRelation('schedules', $empSchedules ?? collect());
        }

        $holidays = $this->getNationalHolidays($year, $month);

        return view('schedule.index', compact(
            'sites',
            'employees',
            'month',
            'year',
            'selectedSiteId',
            'datesInMonth',
            'holidays'
        ));
    }

    /**
     * Generate Rotas & Schedules for Multiple Sites
     */
    public function generate(Request $request)
    {
        $request->validate([
            'target_site_ids'   => 'required|array',
            'target_site_ids.*' => 'exists:sites,id',
            'month'             => 'required',
            'year'              => 'required',
            'employee_ids'      => 'required|array',
            'employee_ids.*'    => 'exists:employees,id',
            'schedule_type'     => 'required|in:office_hour,shift_rotation',
            'start_day'         => 'required|integer|min:1|max:31',
        ]);

        $siteIds       = $request->target_site_ids;
        $month         = sprintf('%02d', $request->month);
        $year          = $request->year;
        $employeeIds   = $request->employee_ids;
        $scheduleType  = $request->schedule_type;
        $startDay      = (int) $request->start_day;

        DB::beginTransaction();
        try {
            // 1. Simpan/Update Pola Kerja (SitePattern) untuk SELURUH Site yang dipilih
            foreach ($siteIds as $siteId) {
                SitePattern::updateOrCreate(
                    ['site_id' => $siteId],
                    [
                        'schedule_type' => $scheduleType,
                        'work_days'     => $request->work_days ?? 6,
                        'off_days'      => $request->off_days ?? 2,
                    ]
                );
            }

            // 2. Tentukan Rentang Periode Tanggal
            $startDate = Carbon::createFromDate($year, $month, $startDay);
            $endDate   = $startDate->copy()->endOfMonth();

            // Ambil Shift
            $offShift = Shift::where('is_off', true)->first();
            $ohShift  = Shift::where('shift_name', 'LIKE', '%Office%')
                ->orWhere('shift_name', 'LIKE', '%OH%')
                ->first() ?? Shift::where('is_off', false)->first();

            // 3. Proses Pembuatan Jadwal untuk Setiap Karyawan Terpilih
            if ($scheduleType === 'office_hour') {
                // ALUR OFFICE HOURS
                foreach ($employeeIds as $empId) {
                    $employee = Employee::find($empId);
                    $lastDate = $employee?->resign_date ? Carbon::parse($employee->resign_date)->endOfDay() : null;

                    $period = CarbonPeriod::create($startDate, $endDate);
                    foreach ($period as $date) {
                        // Hentikan pembuatan jadwal & hapus sisa jika tanggal melewati resign_date karyawan
                        if ($lastDate && $date->greaterThan($lastDate)) {
                            Schedule::where('employee_id', $empId)
                                ->where('date', $date->format('Y-m-d'))
                                ->delete();
                            continue;
                        }

                        $isWeekend = $date->isWeekend();
                        $assignedShiftId = $isWeekend ? ($offShift?->id) : ($ohShift?->id);

                        if ($assignedShiftId) {
                            Schedule::updateOrCreate(
                                [
                                    'employee_id' => $empId,
                                    'date'        => $date->format('Y-m-d'),
                                ],
                                [
                                    'shift_id'    => $assignedShiftId,
                                ]
                            );
                        }
                    }
                }
            } else {
                // ALUR SHIFT ROTATION
                $activeShiftIds = $request->active_shifts ?? [];
                $shiftDuration  = (int) ($request->shift_duration ?? 2);
                $workDays       = (int) ($request->work_days ?? 6);
                $offDays        = (int) ($request->off_days ?? 2);

                if (empty($activeShiftIds)) {
                    return redirect()->back()->withErrors(['active_shifts' => 'Minimal pilih 1 active shift sequence untuk rotasi.']);
                }

                $activeShiftsCount = count($activeShiftIds);

                foreach ($employeeIds as $index => $empId) {
                    $employee = Employee::find($empId);
                    $lastDate = $employee?->resign_date ? Carbon::parse($employee->resign_date)->endOfDay() : null;

                    $period = CarbonPeriod::create($startDate, $endDate);

                    $shiftIndex = $index % $activeShiftsCount;
                    $dayInCurrentShift = 0;
                    $consecutiveWorkDays = 0;
                    $consecutiveOffDays = 0;
                    $isOffMode = false;

                    foreach ($period as $date) {
                        // Hentikan pembuatan jadwal jika melewati resign_date karyawan
                        if ($lastDate && $date->greaterThan($lastDate)) {
                            Schedule::where('employee_id', $empId)
                                ->where('date', $date->format('Y-m-d'))
                                ->delete();
                            continue;
                        }

                        if ($isOffMode) {
                            if ($offShift) {
                                Schedule::updateOrCreate(
                                    ['employee_id' => $empId, 'date' => $date->format('Y-m-d')],
                                    ['shift_id' => $offShift->id]
                                );
                            }
                            $consecutiveOffDays++;

                            if ($consecutiveOffDays >= $offDays) {
                                $isOffMode = false;
                                $consecutiveOffDays = 0;
                                $consecutiveWorkDays = 0;
                                $dayInCurrentShift = 0;
                                $shiftIndex = ($shiftIndex + 1) % $activeShiftsCount;
                            }
                        } else {
                            $currentShiftId = $activeShiftIds[$shiftIndex];

                            Schedule::updateOrCreate(
                                ['employee_id' => $empId, 'date' => $date->format('Y-m-d')],
                                ['shift_id' => $currentShiftId]
                            );

                            $consecutiveWorkDays++;
                            $dayInCurrentShift++;

                            if ($dayInCurrentShift >= $shiftDuration) {
                                $dayInCurrentShift = 0;
                                $shiftIndex = ($shiftIndex + 1) % $activeShiftsCount;
                            }

                            if ($consecutiveWorkDays >= $workDays) {
                                $isOffMode = true;
                                $consecutiveWorkDays = 0;
                            }
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Work schedules successfully generated for the selected sites and employees!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to generate schedules: ' . $e->getMessage()]);
        }
    }

    public function updateSingle(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'shift_id'    => 'required|exists:shifts,id',
        ]);

        try {
            $employee = Employee::find($request->employee_id);
            if ($employee?->resign_date && Carbon::parse($request->date)->greaterThan(Carbon::parse($employee->resign_date)->endOfDay())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengubah jadwal setelah tanggal resign (last date) karyawan.',
                ], 422);
            }

            $schedule = Schedule::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'date'        => $request->date,
                ],
                [
                    'shift_id' => $request->shift_id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Shift updated successfully.',
                'data'    => $schedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update shift: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function clear(Request $request)
    {
        $request->validate([
            'month'   => 'required',
            'year'    => 'required',
            'site_id' => 'required',
        ]);

        $month  = sprintf('%02d', $request->month);
        $year   = $request->year;
        $siteId = $request->site_id;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $employeeQuery = Employee::query();
        if ($siteId !== 'all') {
            $employeeQuery->where('site_id', $siteId);
        }
        $empIds = $employeeQuery->pluck('id');

        Schedule::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->delete();

        return redirect()->back()->with('success', 'Schedule logs for the selected period have been reset.');
    }

    public function export(Request $request)
    {
        $siteId = $request->get('site_id', 'all');
        $month  = sprintf('%02d', $request->get('month', date('m')));
        $year   = $request->get('year', date('Y'));

        if (class_exists('\App\Exports\ScheduleExport')) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ScheduleExport($siteId, $month, $year),
                "Schedules_{$siteId}_{$year}_{$month}.xlsx"
            );
        }

        return redirect()->back()->with('success', 'Export triggered successfully.');
    }

    private function getNationalHolidays($year, $month)
    {
        $holidays = [];
        try {
            $apiUrl = "https://dayoffapi.vercel.app/api?month={$month}&year={$year}";
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item['is_cuti']) && $item['is_cuti']) {
                            continue;
                        }
                        if (isset($item['tanggal']) && isset($item['keterangan'])) {
                            $holidays[$item['tanggal']] = $item['keterangan'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return $holidays;
    }
}
