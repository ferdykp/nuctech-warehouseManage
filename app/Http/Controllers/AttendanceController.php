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

        // KONTROL AKSES SITE SESUAI LOGIN:
        if ($user->role === 'admin_site') {
            $sites = Site::where('id', $user->site_id)->get();
            $siteId = $user->site_id; // Paksa siteId ke site milik admin_site
        } else {
            $sites = Site::all();
            $siteId = $request->input('site_id'); // Bisa berupa ID site atau string 'all'
        }

        $query = Attendance::with(['employee.site']);

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
        try {
            $user = Auth::user();

            // Hak akses multi-tenant
            if ($user && $user->role === 'admin_site' && (int)$user->site_id !== (int)$siteId) {
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
            'site_id' => 'required',
            'month' => 'required'
        ]);

        $siteId = $request->site_id;

        // Security check admin_site
        if ($user->role === 'admin_site') {
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
        $user = Auth::user();
        $month = $this->sanitizeMonth($request->input('month'));
        $siteId = $request->input('site_id');

        if ($user->role === 'admin_site' && (int)$user->site_id !== (int)$siteId) {
            abort(403, 'Anda tidak memiliki akses menyimpan absensi di site ini.');
        }

        if (!$request->has('calendar_raw_data') || empty($request->calendar_raw_data)) {
            return redirect()->back()->with('error', 'Tidak ada data absensi yang dikirim.');
        }

        $daysInMonth = $this->getWorkingDaysCount($month);

        foreach ($request->calendar_raw_data as $employeeId => $matrixJson) {
            if (empty($matrixJson)) {
                continue;
            }

            if ($user->role === 'admin_site') {
                $isMyEmployee = Employee::where('id', $employeeId)->where('site_id', $user->site_id)->exists();
                if (!$isMyEmployee) continue;
            }

            $shiftsArray = json_decode($matrixJson, true);
            $totalKehadiranSesi = 0;

            if (!empty($shiftsArray)) {
                foreach ($shiftsArray as $day => $sessions) {
                    $totalKehadiranSesi += (($sessions['s1'] ?? 0) + ($sessions['s2'] ?? 0) + ($sessions['s3'] ?? 0));
                }
            }

            Attendance::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'month'       => $month,
                ],
                [
                    'working_days'     => $daysInMonth,
                    'attendance_count' => $totalKehadiranSesi,
                    'matrix_details'   => $matrixJson
                ]
            );
        }

        return redirect()->to(route('attendance.index', [
            'site_id'   => $siteId,
            'month'     => $month,
            'auto_full' => $request->input('auto_full', 'true')
        ]))->with('success', 'All bulk employee attendance plot data has been successfully saved!');
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

        if ($user->role === 'admin_site' && (int)$attendance->employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus data rekap absensi site ini.');
        }

        $attendance->delete();

        return redirect()->back()->with('success', 'The employee attendance summary data has been successfully deleted.');
    }
}
