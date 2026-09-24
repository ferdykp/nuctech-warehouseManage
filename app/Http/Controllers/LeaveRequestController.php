<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeSchedule;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = LeaveRequest::with(['employee.site.branch', 'employee.branch', 'leaveType', 'approver']);

        // Filter Role Admin Site
        if (!in_array($user->role, ['superadmin', 'administration'])) {
            $query->whereHas('employee', function ($q) use ($user) {
                $q->whereIn('site_id', \App\Services\SiteAccess::sites($user)->select('id'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $leaveRequests = $query->latest()->paginate(10)->appends($request->all());

        return view('leave.index', compact('leaveRequests'));
    }

    public function create()
    {
        $user = Auth::user();

        $employeesQuery = Employee::where('is_active', true)->with(['site.branch', 'branch']);
        if (!in_array($user->role, ['superadmin', 'administration'])) {
            $employeesQuery->whereIn('site_id', \App\Services\SiteAccess::sites($user)->select('id'));
        }
        $employees = $employeesQuery->get();
        $leaveTypes = LeaveType::all();

        return view('leave.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'reason'          => 'required|string|max:500',
            'attachment_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        \App\Services\SiteAccess::authorize(Employee::findOrFail($request->employee_id)->site_id);
        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        // Hitung total hari (abaikan weekend)
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $totalDays = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend()) {
                $totalDays++;
            }
        }

        if ($totalDays <= 0) {
            return redirect()->back()->withInput()->with('error', 'Tanggal yang dipilih jatuh pada akhir pekan.');
        }

        // Pengecekan Quota jika jenis cuti mengurangi kuota tahunan
        if ($start->year !== $end->year) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'Pisahkan pengajuan cuti untuk tahun yang berbeda.']);
        }
        $currentYear = $start->year;
        if ($leaveType->cut_annual_quota) {
            $balance = EmployeeLeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $request->employee_id,
                    'leave_type_id' => $leaveType->id,
                    'year'          => $currentYear,
                ],
                [
                    'total_quota'     => $leaveType->default_quota,
                    'used_quota'      => 0,
                    'remaining_quota' => $leaveType->default_quota,
                ]
            );

            if ($balance->remaining_quota < $totalDays) {
                return redirect()->back()->withInput()->with(
                    'error',
                    "Sisa kuota cuti karyawan ini tidak mencukupi (Sisa: {$balance->remaining_quota} hari, Diambil: {$totalDays} hari)."
                );
            }
        }

        // Handle upload berkas
        $filePath = null;
        if ($request->hasFile('attachment_file')) {
            $filePath = $request->file('attachment_file')->store('leave_attachments', 'public');
        }

        LeaveRequest::create([
            'employee_id'     => $request->employee_id,
            'leave_type_id'   => $request->leave_type_id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'total_days'      => $totalDays,
            'reason'          => $request->reason,
            'attachment_file' => $filePath,
            'status'          => 'pending',
        ]);

        return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil dibuat dan menunggu persetujuan.');
    }

    public function approve(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['superadmin', 'administration', 'team_leader']), 403);
        DB::transaction(function () use ($id) {
            $leave = LeaveRequest::with('leaveType', 'employee')->lockForUpdate()->findOrFail($id);
            \App\Services\SiteAccess::authorize($leave->employee->site_id);
            if ($leave->status !== 'pending') {
                throw \Illuminate\Validation\ValidationException::withMessages(['leave' => 'Pengajuan sudah diproses.']);
            }
            Employee::whereKey($leave->employee_id)->lockForUpdate()->firstOrFail();
            if ($leave->leaveType->cut_annual_quota) {
                $balance = EmployeeLeaveBalance::firstOrCreate([
                    'employee_id' => $leave->employee_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'year' => $leave->start_date->year,
                ], ['total_quota' => $leave->leaveType->default_quota, 'used_quota' => 0, 'remaining_quota' => $leave->leaveType->default_quota]);
                if ($balance->remaining_quota < $leave->total_days) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['leave' => 'Sisa kuota cuti tidak mencukupi.']);
                }
                $balance->used_quota += $leave->total_days;
                $balance->remaining_quota = $balance->total_quota - $balance->used_quota;
                $balance->save();
            }
            $leave->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        }, 3);
        return back()->with('success', 'Pengajuan cuti berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        abort_unless(in_array(auth()->user()->role, ['superadmin', 'administration', 'team_leader']), 403);
        $data = $request->validate(['rejection_reason' => 'required|string|max:255']);
        DB::transaction(function () use ($id, $data) {
            $leave = LeaveRequest::lockForUpdate()->findOrFail($id);
            \App\Services\SiteAccess::authorize($leave->employee->site_id);
            if ($leave->status !== 'pending') {
                throw \Illuminate\Validation\ValidationException::withMessages(['leave' => 'Pengajuan sudah diproses.']);
            }
            $leave->update($data + ['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        }, 3);
        return back()->with('success', 'Pengajuan cuti berhasil ditolak.');
    }

    /**
     * ============================================================
     * EDIT LEAVE REQUEST
     * ============================================================
     */
    public function edit($id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        \App\Services\SiteAccess::authorize($leaveRequest->employee->site_id);

        // Hanya pengajuan berstatus 'pending' yang boleh diedit
        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('leave.index')->with('error', 'Pengajuan cuti yang sudah diproses tidak dapat diubah.');
        }

        $user = Auth::user();
        $employeesQuery = Employee::where('is_active', true)->with(['site.branch', 'branch']);
        if (!in_array($user->role, ['superadmin', 'administration'])) {
            $employeesQuery->whereIn('site_id', \App\Services\SiteAccess::sites($user)->select('id'));
        }
        $employees = $employeesQuery->get();
        $leaveTypes = LeaveType::all();

        return view('leave.edit', compact('leaveRequest', 'employees', 'leaveTypes'));
    }

    /**
     * ============================================================
     * UPDATE LEAVE REQUEST
     * ============================================================
     */
    public function update(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        \App\Services\SiteAccess::authorize($leaveRequest->employee->site_id);

        if ($leaveRequest->status !== 'pending') {
            return redirect()->route('leave.index')->with('error', 'Pengajuan cuti yang sudah diproses tidak dapat diubah.');
        }

        $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'leave_type_id'   => 'required|exists:leave_types,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'reason'          => 'required|string|max:500',
            'attachment_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        \App\Services\SiteAccess::authorize(Employee::findOrFail($request->employee_id)->site_id);
        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        // Hitung total hari (abaikan weekend)
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $totalDays = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$date->isWeekend()) {
                $totalDays++;
            }
        }

        if ($totalDays <= 0) {
            return redirect()->back()->withInput()->with('error', 'Tanggal yang dipilih jatuh pada akhir pekan.');
        }

        // Pengecekan Quota jika jenis cuti mengurangi kuota tahunan
        if ($start->year !== $end->year) {
            throw \Illuminate\Validation\ValidationException::withMessages(['end_date' => 'Pisahkan pengajuan cuti untuk tahun yang berbeda.']);
        }
        $currentYear = $start->year;
        if ($leaveType->cut_annual_quota) {
            $balance = EmployeeLeaveBalance::firstOrCreate(
                [
                    'employee_id'   => $request->employee_id,
                    'leave_type_id' => $leaveType->id,
                    'year'          => $currentYear,
                ],
                [
                    'total_quota'     => $leaveType->default_quota,
                    'used_quota'      => 0,
                    'remaining_quota' => $leaveType->default_quota,
                ]
            );

            if ($balance->remaining_quota < $totalDays) {
                return redirect()->back()->withInput()->with(
                    'error',
                    "Sisa kuota cuti karyawan ini tidak mencukupi (Sisa: {$balance->remaining_quota} hari, Diambil: {$totalDays} hari)."
                );
            }
        }

        // Handle update berkas
        if ($request->hasFile('attachment_file')) {
            if ($leaveRequest->attachment_file && Storage::disk('public')->exists($leaveRequest->attachment_file)) {
                Storage::disk('public')->delete($leaveRequest->attachment_file);
            }
            $leaveRequest->attachment_file = $request->file('attachment_file')->store('leave_attachments', 'public');
        }

        $leaveRequest->update([
            'employee_id'   => $request->employee_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => $totalDays,
            'reason'        => $request->reason,
        ]);

        return redirect()->route('leave.index')->with('success', 'Data pengajuan cuti berhasil diperbarui.');
    }

    /**
     * ============================================================
     * DESTROY LEAVE REQUEST
     * ============================================================
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $leaveRequest = LeaveRequest::with('leaveType')->lockForUpdate()->findOrFail($id);
            \App\Services\SiteAccess::authorize($leaveRequest->employee->site_id);
            abort_if(auth()->user()->role === 'employee_role' && $leaveRequest->status !== 'pending', 403);
            Employee::whereKey($leaveRequest->employee_id)->lockForUpdate()->firstOrFail();
            // Jika cuti yang dihapus berstatus 'approved' dan memotong kuota, kembalikan kuotanya
            if ($leaveRequest->status === 'approved' && $leaveRequest->leaveType->cut_annual_quota) {
                $year = Carbon::parse($leaveRequest->start_date)->year;
                $balance = EmployeeLeaveBalance::where('employee_id', $leaveRequest->employee_id)
                    ->where('leave_type_id', $leaveRequest->leave_type_id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $balance->used_quota = max(0, $balance->used_quota - $leaveRequest->total_days);
                    $balance->remaining_quota = min($balance->total_quota, $balance->total_quota - $balance->used_quota);
                    $balance->save();
                }
            }

            // Hapus file berkas jika ada
            if ($leaveRequest->attachment_file && Storage::disk('public')->exists($leaveRequest->attachment_file)) {
                Storage::disk('public')->delete($leaveRequest->attachment_file);
            }

            $leaveRequest->delete();
        });

        return redirect()->route('leave.index')->with('success', 'Data pengajuan cuti berhasil dihapus.');
    }
}
