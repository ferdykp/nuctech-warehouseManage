<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_quota',
        'is_paid',
        'cut_annual_quota',
        'requires_file',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'cut_annual_quota' => 'boolean',
        'requires_file' => 'boolean',
    ];

    public function leaveBalances()
    {
        return $this->hasMany(EmployeeLeaveBalance::class, 'leave_type_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}
