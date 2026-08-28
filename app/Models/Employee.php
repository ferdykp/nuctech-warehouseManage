<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'branch_id',
        'name',
        'email',
        'nik',
        'phone_number',
        'position',
        'status',
        'basic_salary',
        'bank_name',
        'bank_account_number',
        'mcu',
        'tld',
        'join_date',
        'contract_start_date',
        'is_active',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class, 'employee_id')->latest();
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'employee_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    // --- RELASI MODUL CUTI & IZIN ---

    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class, 'employee_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    // --- RELASI MODUL TUKAR SHIFT / LEMBUR PENGGANTI ---

    // Sebagai karyawan B (yang digantikan/cuti)
    public function shiftReplacementsAsOriginal()
    {
        return $this->hasMany(ShiftReplacementRequest::class, 'original_employee_id');
    }

    // Sebagai karyawan A (pengganti yang dapat lemburan)
    public function shiftReplacementsAsReplacement()
    {
        return $this->hasMany(ShiftReplacementRequest::class, 'replacement_employee_id');
    }

    /**
     * Menghitung Otomatis Status Informasi Gaji Berdasarkan Tanggal Join & Status Karyawan
     */
    public function getCalculatedSalaryInformation($targetDate = null)
    {
        if ($this->status !== 'Probation') {
            return 'regular salary';
        }

        $joinDate = $this->join_date ? Carbon::parse($this->join_date)->startOfMonth() : Carbon::now()->startOfMonth();
        $payrollDate = $targetDate ? Carbon::parse($targetDate)->startOfMonth() : Carbon::now()->startOfMonth();

        // Hitung selisih bulan antara tanggal gabung dengan periode gaji
        $diffInMonths = $joinDate->diffInMonths($payrollDate, false);

        if ($diffInMonths <= 1) {
            return '1st probation';
        } elseif ($diffInMonths == 2) {
            return '2nd probation';
        } elseif ($diffInMonths == 3) {
            return '3rd probation';
        } else {
            return 'regular salary';
        }
    }
}
