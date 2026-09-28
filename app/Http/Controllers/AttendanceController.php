<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Exports\AttendanceExport;
use App\Models\Employee;
use App\Models\Site;
use App\Services\IndonesianHolidayService;
use App\Services\AttendanceCalendar;
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

        // KONTROL AKSES SITE SESUAI LOGIN:
        if ($user->role === 'employee_role') {
            $sites = Site::where('id', $user->site_id)->get();
            $siteId = $user->site_id; // Paksa siteId ke site milik employee_role
        } else {
            $sites = Site::all();
            $siteId = $request->input('site_id'); // Bisa berupa ID site atau string 'all'
        }

        $query = Attendance::with(['employee.site', 'employee.schedules' => fn ($q) => $q->whereBetween('date', [$month.'-01', Carbon::parse($month.'-01')->endOfMonth()->toDateString()])->with('shift')]);

        // Jika siteId diisi dan bukan 'all', filter berdasarkan site_id
        if (!empty($siteId) && $siteId !== 'all') {
            $query->whereHas('employee', function ($q) use ($siteId) {
                $q->where('site_id', $siteId);
            });
        }

        $query->where('month', $month);
        $attendances = $query->get();
        foreach ($attendances as $attendance) {
            if ($attendance->employee?->resign_date && $attendance->matrix_details) {
                $calendar = new AttendanceCalendar();
                $matrix = $calendar->matrix($attendance->employee, $month, json_decode($attendance->matrix_details, true) ?? []);
                $attendance->matrix_details = json_encode($matrix);
                $attendance->attendance_count = $calendar->count($matrix);
                $attendance->working_days = $calendar->count($calendar->matrix($attendance->employee, $month));
            }
        }

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

            $employeesQuery->where(fn ($q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', $startDate));
            $employees = $employeesQuery->get();
            $this->prepareEmployees($employees, $month);
        }

        $holidays = $this->holidayService->getHolidaysForMonth($month);

        return view('attendance.index', compact('sites', 'attendances', 'employees', 'holidays', 'siteId'));
    }

    /**
     * Endpoint API yang dipanggil oleh fetch('/api/branches/{siteId}/employees')
     */
    public function getEmployeesByBranch(Request $request, $siteId)
    {
        try {
            $user = Auth::user();

            // Hak akses multi-tenant
            if ($user && $user->role === 'employee_role' && (int)$user->site_id !== (int)$siteId) {
                return response()->json(['message' => 'Akses ditolak untuk site ini.'], 403);
            }

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

            $employeesQuery->where(fn ($q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', $startDate));
            $employees = $employeesQuery->get();
            $this->prepareEmployees($employees, $month);

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
            'site_id' => 'required',
            'month' => 'required'
        ]);

        $siteId = $request->site_id;

        // Security check employee_role
        if ($user->role === 'employee_role') {
            $siteId = $user->site_id; // Paksa admin site hanya bisa ekspor site milik sendiri
        }

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
        $user = Auth::user();
        abort_if($user->role === 'employee_role' && (int) $user->site_id !== (int) $data['site_id'], 403);
        $calendar = new AttendanceCalendar();
        $daysInMonth = Carbon::createFromFormat('!Y-m', $data['month'])->daysInMonth;
        $rows = [];
        foreach ($data['calendar_raw_data'] as $employeeId => $json) {
            $employee = Employee::with(['schedules' => fn ($q) => $q->whereBetween('date', [$data['month'].'-01', $data['month'].'-'.$daysInMonth])->with('shift')])->findOrFail($employeeId);
            abort_if($user->role === 'employee_role' && (int) $employee->site_id !== (int) $user->site_id, 403);
            abort_if($data['site_id'] !== 'all' && (int) $employee->site_id !== (int) $data['site_id'], 403);
            $matrix = json_decode($json, true);
            \Illuminate\Support\Facades\Validator::make(['matrix' => $matrix], [
                'matrix' => 'required|array', 'matrix.*' => 'array:s1,s2,s3',
                'matrix.*.s1' => 'required|integer|between:0,1',
                'matrix.*.s2' => 'required|integer|between:0,1',
                'matrix.*.s3' => 'required|integer|between:0,1',
            ])->validate();
            foreach ($matrix as $day => $sessions) {
                if (!ctype_digit((string) $day) || (int) $day < 1 || (int) $day > $daysInMonth) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['calendar_raw_data' => 'Tanggal absensi tidak valid.']);
                }
                $date = Carbon::createFromFormat('!Y-m', $data['month'])->day((int) $day);
                if ($employee->resign_date && $date->gt($employee->resign_date) && array_sum($sessions) > 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['calendar_raw_data' => 'Absensi tidak boleh diisi setelah Last Date '.$employee->resign_date->format('d-m-Y').'.']);
                }
            }
            $normalized = $calendar->matrix($employee, $data['month'], $matrix);
            $rows[$employeeId] = [
                'working_days' => $calendar->count($calendar->matrix($employee, $data['month'])),
                'attendance_count' => $calendar->count($normalized),
                'matrix_details' => json_encode($normalized),
            ];
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $data) {
            foreach ($rows as $employeeId => $row) {
                Attendance::updateOrCreate(['employee_id' => $employeeId, 'month' => $data['month']], $row);
            }
        });
        return redirect()->route('attendance.index', ['site_id' => $data['site_id'], 'month' => $data['month'], 'auto_full' => $request->input('auto_full', 'true')])
            ->with('success', 'Data absensi berhasil disimpan sesuai batas Last Date.');
    }

    private function prepareEmployees($employees, string $month): void
    {
        $calendar = new AttendanceCalendar();
        foreach ($employees as $employee) {
            $employee->last_working_date = $employee->resign_date?->format('Y-m-d');
            $employee->scheduled_attendance = $calendar->matrix($employee, $month);
            if ($employee->resign_date) {
                $employee->setRelation('schedules', $employee->schedules->filter(fn ($s) => $s->date->lte($employee->resign_date))->values());
                foreach ($employee->attendances as $attendance) {
                    $matrix = $calendar->matrix($employee, $month, json_decode($attendance->matrix_details ?? '{}', true) ?? []);
                    $attendance->matrix_details = json_encode($matrix);
                    $attendance->attendance_count = $calendar->count($matrix);
                    $attendance->working_days = $calendar->count($employee->scheduled_attendance);
                }
            }
        }
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

        $attendance->delete();

        return redirect()->back()->with('success', 'The employee attendance summary data has been successfully deleted.');
    }
}
