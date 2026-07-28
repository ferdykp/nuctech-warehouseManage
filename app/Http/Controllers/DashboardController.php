<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use App\Models\SparepartStock;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Services\IndonesianHolidayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected IndonesianHolidayService $holidayService;

    public function __construct(IndonesianHolidayService $holidayService)
    {
        $this->holidayService = $holidayService;
    }
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        if ($user->role === 'superadmin') {
            // Metrik Superadmin (Seluruh Site)
            $totalBranch = Site::count();
            $totalEmployee = Employee::count();
            $totalSparepart = SparepartStock::sum('qty') ?? 0; //[cite: 1]
            $totalMachine = Site::count();
            $criticalStock = SparepartStock::where('qty', '<=', 5)->count(); //[cite: 1]

            // Ambil jadwal karyawan yang bekerja HARI INI (Menggunakan model Schedule)
            $todaysSchedules = EmployeeSchedule::with(['employee.site', 'shift'])
                ->where('date', $today)
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->take(6)
                ->get();
        } else {
            // Metrik Site Admin (Sesuai Site Mereka)
            $siteId = $user->site_id;
            $totalBranch = 1;
            $totalEmployee = Employee::where('site_id', $siteId)->count();
            $totalSparepart = SparepartStock::where('site_id', $siteId)->sum('qty') ?? 0; //[cite: 1]
            $totalMachine = 1;
            $criticalStock = SparepartStock::where('site_id', $siteId)->where('qty', '<=', 5)->count(); //[cite: 1]

            $todaysSchedules = EmployeeSchedule::with(['employee.site', 'shift'])
                ->whereHas('employee', function ($q) use ($siteId) {
                    $q->where('site_id', $siteId);
                })
                ->where('date', $today)
                ->whereHas('shift', function ($q) {
                    $q->where('is_off', false);
                })
                ->take(6)
                ->get();
        }
        $currentMonth = now()->format('Y-m');

        $upcomingHolidays = collect(
            $this->holidayService->getHolidaysForMonth($currentMonth)
        )
            ->filter(function ($name, $date) {
                return Carbon::parse($date)->gte(today());
            })
            ->map(function ($name, $date) {
                return [
                    'date' => Carbon::parse($date)->translatedFormat('d F'),
                    'name' => $name,
                ];
            })
            ->values();

        return view('dashboard.index', compact(
            'totalBranch',
            'totalEmployee',
            'totalSparepart',
            'totalMachine',
            'criticalStock',
            'todaysSchedules',
            'upcomingHolidays'
        ));
    }
}
