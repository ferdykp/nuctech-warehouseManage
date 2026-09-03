<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSalaryHistory;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\EmployeesExport;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;


class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $employeesQuery = Employee::with(['site.branch']);

        if ($user->role === 'employee_role') {
            $employeesQuery->where('site_id', $user->site_id);
            $sites = Site::where('id', $user->site_id)->get();
        } else {
            $sites = Site::all();
            if ($request->filled('site_id') && $request->site_id !== 'all') {
                $employeesQuery->where('site_id', $request->site_id);
            }
        }

        if (!empty($search)) {
            $employeesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('nik', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%')
                    ->orWhere('phone_number', 'like', '%' . $search . '%')
                    ->orWhere('bank_account_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $employeesQuery->where('status', $request->status);
        }

        $employees = $employeesQuery->latest()->paginate(10)->appends($request->all());

        if ($request->ajax() && !$request->wantsJson()) {
            return view('employee.table', compact('employees'))->render();
        }

        return view('employee.index', compact('sites', 'employees'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'site_id'             => 'required|exists:sites,id',
            'name'                => 'required|string|max:255',
            'email'               => 'nullable|email|unique:employees,email|max:255',
            'nik'                 => 'nullable|string|max:16|unique:employees,nik',
            'phone_number'        => 'required|string|max:20',
            'position'            => 'nullable|string|max:100',
            'status'              => 'required|in:Permanent,Contract,Probation,Daily',
            'basic_salary'        => 'nullable|numeric|min:0',
            'bank_name'           => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'mcu'                 => 'nullable|in:yes,no',
            'tld'                 => 'nullable|in:yes,no',
            'join_date'           => 'required|date',
            'contract_start_date' => 'nullable|date',
        ]);

        $siteId = ($user->role === 'employee_role') ? $user->site_id : $validatedData['site_id'];
        $site = Site::findOrFail($siteId);

        $basicSalary = $validatedData['basic_salary'] ?? 0;

        $validatedData['site_id']      = $site->id;
        $validatedData['branch_id']    = $site->branch_id;
        $validatedData['basic_salary'] = $basicSalary;
        $validatedData['mcu']          = $validatedData['mcu'] ?? 'no';
        $validatedData['tld']          = $validatedData['tld'] ?? 'no';
        $validatedData['is_active']    = true;

        $employee = Employee::create($validatedData);

        if ($basicSalary > 0) {
            EmployeeSalaryHistory::create([
                'employee_id' => $employee->id,
                'old_salary'  => 0,
                'new_salary'  => $basicSalary,
                'reason'      => 'Gaji Awal Karyawan Baru',
                'updated_by'  => $user->name ?? 'System Admin',
            ]);
        }

        return redirect()->route('employee.index')->with('success', 'The new employee has been successfully registered.!');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $employeeId = is_object($id) ? $id->id : $id;
        $employee = Employee::findOrFail($employeeId);

        if ($user->role === 'employee_role' && (int)$employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses mengubah karyawan di site ini.');
        }

        $validatedData = $request->validate([
            'site_id'              => 'required|exists:sites,id',
            'name'                 => 'required|string|max:255',
            'email'                => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
            'nik'                  => 'nullable|string|max:16|unique:employees,nik,' . $employee->id,
            'phone_number'         => 'required|string|max:20',
            'position'             => 'nullable|string|max:100',
            'status'               => 'required|in:Permanent,Contract,Probation,Daily',
            'basic_salary'         => 'nullable|numeric|min:0',
            'bank_name'            => 'nullable|string|max:100',
            'bank_account_number'  => 'nullable|string|max:100',
            'mcu'                  => 'nullable|in:yes,no',
            'tld'                  => 'nullable|in:yes,no',
            'salary_change_reason' => 'nullable|string|max:255',
            'join_date'            => 'required|date',
            'contract_start_date'  => 'nullable|date',
        ]);

        $newSalary = $validatedData['basic_salary'] ?? 0;

        if ((float)$employee->basic_salary !== (float)$newSalary) {
            EmployeeSalaryHistory::create([
                'employee_id' => $employee->id,
                'old_salary'  => $employee->basic_salary ?? 0,
                'new_salary'  => $newSalary,
                'reason'      => $request->input('salary_change_reason') ?: 'Penyesuaian Gaji / Promosi',
                'updated_by'  => $user->name ?? 'System Admin',
            ]);
        }

        $siteId = ($user->role === 'employee_role') ? $user->site_id : $validatedData['site_id'];
        $site = Site::findOrFail($siteId);

        $validatedData['site_id']   = $site->id;
        $validatedData['branch_id'] = $site->branch_id;
        $validatedData['mcu']       = $validatedData['mcu'] ?? $employee->mcu ?? 'no';
        $validatedData['tld']       = $validatedData['tld'] ?? $employee->tld ?? 'no';

        $employee->update($validatedData);

        return redirect()->route('employee.index')->with('success', 'Employee data successfully updated.!');
    }

    // public function show($id)
    // {
    //     try {
    //         $user = Auth::user();

    //         $employeeId = is_object($id) ? $id->id : $id;
    //         $employee = Employee::with(['site.branch', 'salaryHistories'])->findOrFail($employeeId);

    //         if ($user && $user->role === 'employee_role' && (int)$employee->site_id !== (int)$user->site_id) {
    //             return response()->json(['message' => 'Anda tidak memiliki akses ke data karyawan ini.'], 403);
    //         }

    //         $joinDate = Carbon::parse($employee->join_date);
    //         $diff = $joinDate->diff(Carbon::now());

    //         $tenureParts = [];
    //         if ($diff->y > 0) $tenureParts[] = $diff->y . ' Tahun';
    //         if ($diff->m > 0) $tenureParts[] = $diff->m . ' Bulan';
    //         $tenureParts[] = $diff->d . ' Hari';

    //         $employee->tenure_formatted = implode(' ', $tenureParts);
    //         $employee->join_date_formatted = $joinDate->translatedFormat('d F Y');
    //         $employee->contract_start_formatted = $employee->contract_start_date
    //             ? Carbon::parse($employee->contract_start_date)->translatedFormat('d F Y')
    //             : '-';
    //         $employee->basic_salary_formatted = 'Rp ' . number_format($employee->basic_salary ?? 0, 0, ',', '.');

    //         // Tambahkan format string untuk status MCU & TLD di respon JSON
    //         $employee->mcu_formatted = strtoupper($employee->mcu ?? 'no');
    //         $employee->tld_formatted = strtoupper($employee->tld ?? 'no');

    //         return response()->json($employee);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Gagal mengambil data karyawan: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function show($id)
    {
        try {
            $user = Auth::user();
            $employeeId = is_object($id) ? $id->id : $id;

            // Cegah eksekusi jika $id bukan angka (misal melempar string 'export')
            if (!is_numeric($employeeId)) {
                return response()->json(['message' => 'Invalid Employee ID'], 400);
            }

            $employee = Employee::with(['site.branch', 'salaryHistories'])->findOrFail($employeeId);

            if ($user && $user->role === 'employee_role' && (int)$employee->site_id !== (int)$user->site_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke data karyawan ini.'], 403);
            }

            $joinDate = Carbon::parse($employee->join_date);
            $diff = $joinDate->diff(Carbon::now());

            $tenureParts = [];
            if ($diff->y > 0) $tenureParts[] = $diff->y . ' Tahun';
            if ($diff->m > 0) $tenureParts[] = $diff->m . ' Bulan';
            $tenureParts[] = $diff->d . ' Hari';

            $employee->tenure_formatted = implode(' ', $tenureParts);
            $employee->join_date_formatted = $joinDate->translatedFormat('d F Y');
            $employee->contract_start_formatted = $employee->contract_start_date
                ? Carbon::parse($employee->contract_start_date)->translatedFormat('d F Y')
                : '-';
            $employee->basic_salary_formatted = 'Rp ' . number_format($employee->basic_salary ?? 0, 0, ',', '.');
            $employee->mcu_formatted = strtoupper($employee->mcu ?? 'no');
            $employee->tld_formatted = strtoupper($employee->tld ?? 'no');

            return response()->json($employee);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $user = Auth::user();

        $employeeId = is_object($id) ? $id->id : $id;
        $employee = Employee::with('salaryHistories')->findOrFail($employeeId);

        if ($user->role === 'employee_role' && (int)$employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses mengubah karyawan di site ini.');
        }

        if ($user->role === 'employee_role') {
            $sites = Site::where('id', $user->site_id)->with('branch')->get();
        } else {
            $sites = Site::with('branch')->get();
        }

        return view('employee.edit', compact('employee', 'sites'));
    }

    /**
     * API Internal: Ambil daftar karyawan per site/branch (atau semua site) beserta absensi & jadwalnya
     */
    public function getEmployeesByBranch(Request $request, $site_id)
    {
        try {
            $user = Auth::user();

            if ($user && $user->role === 'employee_role' && (int)$user->site_id !== (int)$site_id) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke site ini.'], 403);
            }

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

            $employeesQuery = Employee::with([
                'site',
                'attendances' => function ($q) use ($month) {
                    $q->where('month', $month);
                },
                'schedules' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate])->with('shift');
                }
            ]);

            // Jika site_id bukan 'all', lakukan filter per site
            if ($site_id !== 'all') {
                $employeesQuery->where('site_id', $site_id);
            }

            $employees = $employeesQuery->get();

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

        if ($user->role === 'employee_role') {
            $sites = Site::where('id', $user->site_id)->with('branch')->get();
        } else {
            $sites = Site::with('branch')->get();
        }

        return view('employee.create', compact('sites'));
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $employeeId = is_object($id) ? $id->id : $id;
        $employee = Employee::findOrFail($employeeId);

        if ($user->role === 'employee_role' && (int)$employee->site_id !== (int)$user->site_id) {
            abort(403, 'Anda tidak memiliki akses menghapus karyawan di site ini.');
        }

        $employee->attendances()->delete();
        $employee->schedules()->delete();
        $employee->salaryHistories()->delete();
        $employee->delete();

        return redirect()->route('employee.index')->with('success', 'The employee and their entire work history have been successfully deleted..');
    }


    public function export(Request $request)
    {
        $user = Auth::user();
        $siteId = $request->input('site_id');

        if ($user && $user->role === 'employee_role') {
            $siteId = $user->site_id;
        }

        $fileName = 'Employee_List_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Dipanggil tanpa parameter filter karena EmployeesExport mengekspor seluruh data
        return Excel::download(new EmployeesExport(), $fileName);
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $user = Auth::user();
        $siteId = ($user->role === 'employee_role') ? $user->site_id : $request->input('site_id');

        try {
            Excel::import(new EmployeesImport($siteId), $request->file('file'));
            return redirect()->back()->with('success', 'Employee data imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to import employee data: ' . $e->getMessage());
        }
    }
}
