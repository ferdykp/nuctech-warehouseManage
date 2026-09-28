<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Exports\AttendanceExport;
use App\Models\Employee;
use App\Models\Site;
use App\Services\IndonesianHolidayService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(private IndonesianHolidayService $holidayService) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Dapatkan & bersihkan variabel $month secara aman
        $monthInput = $request->input('month');
        $month = $this->sanitizeMonth($monthInput);

        $sites = \App\Services\SiteAccess::sites($user, true)->get();
        $siteId = $request->input('site_id', $user->role === 'employee_role' ? $user->site_id : null);
        if ($siteId && $siteId !== 'all') \App\Services\SiteAccess::authorize($siteId, true);
        $query = Attendance::with(['employee.site'])->whereHas('employee', fn ($q) => $q->whereIn('site_id', $sites->pluck('id')));

        // Jika siteId diisi dan bukan 'all', filter berdasarkan site_id
        if (!empty($siteId) && $siteId !== 'all') {
            $query->whereHas('employee', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        $query->where('month', $month);
        $attendances = $query->get();

        $employees = [];
        if ($siteId) {
            try {
                $startDate = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                $month = date('Y-m');
                $startDate = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');
            }

            $employeesQuery = Employee::with([
                'site',
                'attendances' => function ($q) use ($month) {
                    $q->where('month', $month);
                },
                'schedules' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])->with('shift');
                }
            ]);

            if ($siteId !== 'all') {
                $employeesQuery->where('site_id', $siteId);
            }

            $employeesQuery->whereIn('site_id', \App\Services\SiteAccess::sites($user, true)->select('id'));
            $employees = $employeesQuery->get();
        }

        $holidays = $this->holidayService->getHolidaysForMonth($month);

        return view('attendance.index', compact('sites', 'attendances', 'employees', 'holidays', 'siteId'));
    }

    /**
     * Endpoint API yang dipanggil oleh fetch('/api/branches/{siteId}/employees')
     */
    public function getEmployeesByBranch(Request $request, $siteId)
    {
        if ($siteId !== 'all') \App\Services\SiteAccess::authorize($siteId, true);
        try {
            $user = Auth::user();

            $month = $this->sanitizeMonth($request->input('month'));

            $startDate = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

            $employeesQuery = Employee::with([
                'site',
                'attendances' => function ($q) use ($month) {
                    $q->where('month', $month);
                },
                'schedules' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])->with('shift');
                }
            ]);

            // Filter site hanya jika siteId bukan 'all'
            if ($siteId !== 'all') {
                $employeesQuery->where('site_id', $siteId);
            }

            $employeesQuery->whereIn('site_id', \App\Services\SiteAccess::sites($user, true)->select('id'));
            $employees = $employeesQuery->get();

            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'site_id' => 'required|string',
            'month' => 'required|date_format:Y-m'
        ]);

        $siteId = $request->site_id;

        if ($siteId !== 'all') \App\Services\SiteAccess::authorize($siteId, true);

        if ($siteId === 'all') {
            $filename = 'Rekap_Absensi_Semua_Site_' . $request->month . '.xlsx';
        } else {
            $site = Site::findOrFail($siteId);
            $filename = 'Rekap_Absensi_' . str_replace(' ', '_', $site->machine_name) . '_' . $request->month . '.xlsx';
        }

        return Excel::download(new AttendanceExport($siteId, $request->month), $filename);
    }

    public function storeAttendance(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'site_id' => 'required',
            'calendar_raw_data' => 'required|array|min:1',
            'calendar_raw_data.*' => 'required|json',
        ]);
        if ($data['site_id'] !== 'all') \App\Services\SiteAccess::authorize($data['site_id'], true);
        $days = Carbon::createFromFormat('!Y-m', $data['month'])->daysInMonth;
        $rows = [];
        foreach ($data['calendar_raw_data'] as $employeeId => $json) {
            $employee = Employee::findOrFail($employeeId);
            \App\Services\SiteAccess::authorize($employee->site_id, true);
            abort_if($data['site_id'] !== 'all' && (int) $employee->site_id !== (int) $data['site_id'], 403);
            $matrix = json_decode($json, true);
            \Illuminate\Support\Facades\Validator::make(['matrix' => $matrix], [
                'matrix' => 'required|array', 'matrix.*' => 'required|array:s1,s2,s3',
                'matrix.*.s1' => 'required|integer|between:0,1',
                'matrix.*.s2' => 'required|integer|between:0,1',
                'matrix.*.s3' => 'required|integer|between:0,1',
            ])->validate();
            $count = 0;
            foreach ($matrix as $day => $sessions) {
                if (!ctype_digit((string) $day) || (int) $day < 1 || (int) $day > $days) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['calendar_raw_data' => 'Tanggal absensi tidak valid.']);
                }
                $count += array_sum($sessions);
            }
            $rows[$employeeId] = ['attendance_count' => $count, 'matrix_details' => json_encode($matrix)];
        }
        $workingDays = $this->getWorkingDaysCount($data['month']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $data, $workingDays) {
            foreach ($rows as $employeeId => $row) {
                Employee::whereKey($employeeId)->lockForUpdate()->firstOrFail();
                Attendance::updateOrCreate(['employee_id' => $employeeId, 'month' => $data['month']], $row + ['working_days' => $workingDays]);
            }
        }, 3);
        return redirect()->route('attendance.index', ['site_id' => $data['site_id'], 'month' => $data['month']])->with('success', 'Data absensi berhasil disimpan.');
    }

    private function getWorkingDaysCount(string $monthString): int
    {
        try {
            $date = Carbon::parse($monthString . '-01');
        } catch (\Exception $e) {
            $date = Carbon::now();
            $monthString = $date->format('Y-m');
        }

        $daysInMonth = $date->daysInMonth;
        $holidays = $this->holidayService->getHolidaysForMonth($monthString);

        $workingDaysCount = 0;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::parse($monthString . '-' . str_pad($day, 2, '0', STR_PAD_LEFT));

            if ($currentDate->isWeekend()) {
                continue;
            }

            if (isset($holidays[$currentDate->toDateString()])) {
                continue;
            }

            $workingDaysCount++;
        }

        return $workingDaysCount;
    }

    private function sanitizeMonth(?string $monthInput): string
    {
        if (empty($monthInput) || $monthInput === '-') {
            return date('Y-m');
        }

        try {
            return Carbon::parse($monthInput . '-01')->format('Y-m');
        } catch (\Exception $e) {
            return date('Y-m');
        }
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $user = Auth::user();

        if ($user->role === 'employee_role' && (int)$attendance->employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data rekap absensi site ini.');
        }

        \App\Services\SiteAccess::authorize($attendance->employee->site_id, true);
        $attendance->delete();

        return redirect()->back()->with('success', 'The employee attendance summary data has been successfully deleted.');
    }
}
