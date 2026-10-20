<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeAllowanceController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['nullable', 'date_format:Y-m', 'after_or_equal:start_month'],
            'notes' => ['nullable', 'string', 'max:500'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        unset($data['certificate']);
        $data['certificate_path'] = $request->file('certificate')?->store('allowance-certificates', 'local');
        try {
            $employee->allowances()->create($data);
        } catch (\Throwable $error) {
            if ($data['certificate_path']) Storage::disk('local')->delete($data['certificate_path']);
            throw $error;
        }
        return back()->with('success', 'Tunjangan bulanan ditambahkan. Payroll baru akan memasukkannya otomatis; payroll tersimpan tetap dipertahankan.');
    }

    public function end(Request $request, Employee $employee, EmployeeAllowance $allowance)
    {
        abort_unless($allowance->employee_id === $employee->id, 404);
        $data = $request->validate(['end_month' => ['required', 'date_format:Y-m']]);
        if ($data['end_month'] < $allowance->start_month || $data['end_month'] < now()->format('Y-m')) {
            throw ValidationException::withMessages(['end_month' => 'Bulan terakhir dibayar minimal bulan ini dan tidak boleh sebelum bulan mulai.']);
        }
        $allowance->update($data);
        return back()->with('success', 'Bulan terakhir pembayaran tunjangan diperbarui. Payroll tersimpan tidak berubah.');
    }

    public function certificate(Employee $employee, EmployeeAllowance $allowance)
    {
        abort_unless($allowance->employee_id === $employee->id && $allowance->certificate_path, 404);
        abort_unless(Storage::disk('local')->exists($allowance->certificate_path), 404);
        return Storage::disk('local')->download($allowance->certificate_path);
    }
}
