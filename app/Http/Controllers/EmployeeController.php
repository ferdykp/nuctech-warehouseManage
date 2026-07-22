<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $employeesQuery = Employee::with(['site.branch']);

        // Akses berdasarkan Role
        if ($user->role === 'admin_site') {
            $employeesQuery->where('site_id', $user->site_id);
            $sites = Site::where('id', $user->site_id)->get();
        } else {
            $sites = Site::all();
            if ($request->filled('site_id')) {
                $employeesQuery->where('site_id', $request->site_id);
            }
        }

        if (!empty($search)) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $employeesQuery->where('status', $request->status);
        }

        $employees = $employeesQuery->latest()->paginate(10);

        if ($request->ajax()) {
            return view('employee.table', compact('employees'))->render();
        }

        return view('employee.index', compact('sites', 'employees'));
    }

    /**
     * API Internal: Ambil daftar karyawan per site/branch beserta absensi & jadwalnya
     */
    public function getEmployeesByBranch(Request $request, $site_id)
    {
        try {
            $user = Auth::user();

            // Proteksi Admin Site
            if ($user && $user->role === 'admin_site' && (int)$user->site_id !== (int)$site_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke site ini.'], 403);
            }

            // Dapatkan filter bulan (Default bulan berjalan YYYY-MM)
            $monthInput = $request->input('month');
            if (empty($monthInput) || $monthInput === '-') {
                $month = date('Y-m');
            } else {
                try {
                    $month = Carbon::parse($monthInput . '-01')->format('Y-m');
                } catch (\Exception $e) {
                    $month = date('Y-m');
                }
            }

            $startDate = Carbon::parse($month . '-01')->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::parse($month . '-01')->endOfMonth()->format('Y-m-d');

            $employees = Employee::where('site_id', $site_id)
                ->with([
                    'attendances' => function ($q) use ($month) {
                        $q->where('month', $month);
                    },
                    'schedules' => function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('date', [$startDate, $endDate])->with('shift');
                    }
                ])
                ->get();

            return response()->json($employees);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mengambil data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->role === 'admin_site') {
            $sites = Site::where('id', $user->site_id)->with('branch')->get();
        } else {
            $sites = Site::with('branch')->get();
        }

        return view('employee.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'site_id'             => 'required|exists:sites,id',
            'name'                => 'required|string|max:255',
            'position'            => 'nullable|string|max:100',
            'status'              => 'required|in:Permanent,Contract,Probation,Daily',
            'join_date'           => 'required|date',
            'contract_start_date' => 'nullable|date',
        ]);

        // JIKA ADMIN SITE: Paksa site_id ke site milik user
        if ($user->role === 'admin_site') {
            $siteId = $user->site_id;
        } else {
            $siteId = $validatedData['site_id'];
        }

        // AMBIL BRANCH_ID OTOMATIS DARI MAPPING SITE
        $site = Site::findOrFail($siteId);

        $validatedData['site_id']   = $site->id;
        $validatedData['branch_id'] = $site->branch_id;
        $validatedData['is_active'] = true;

        Employee::create($validatedData);

        return redirect()->route('employee.index')->with('success', 'Karyawan baru berhasil terdaftar!');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $employee = Employee::findOrFail($id);

        if ($user->role === 'admin_site' && (int)$employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses mengubah karyawan di site ini.');
        }

        $validatedData = $request->validate([
            'site_id'             => 'required|exists:sites,id',
            'name'                => 'required|string|max:255',
            'position'            => 'nullable|string|max:100',
            'status'              => 'required|in:Permanent,Contract,Probation,Daily',
            'join_date'           => 'required|date',
            'contract_start_date' => 'nullable|date',
            'is_active'           => 'nullable|boolean'
        ]);

        if ($user->role === 'admin_site') {
            $siteId = $user->site_id;
        } else {
            $siteId = $validatedData['site_id'];
        }

        $site = Site::findOrFail($siteId);

        $validatedData['site_id']   = $site->id;
        $validatedData['branch_id'] = $site->branch_id;

        $employee->update($validatedData);

        return redirect()->route('employee.index')->with('success', 'Data karyawan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $employee = Employee::findOrFail($id);

        if ($user->role === 'admin_site' && (int)$employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses menghapus karyawan di site ini.');
        }

        $employee->attendances()->delete();
        $employee->schedules()->delete();
        $employee->delete();

        return redirect()->route('employee.index')->with('success', 'Karyawan beserta seluruh riwayat kerjanya berhasil dihapus.');
    }
}
