<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftReplacementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'original_employee_id',
        'replacement_employee_id',
        'date',
        'shift_id',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function originalEmployee()
    {
        return $this->belongsTo(Employee::class, 'original_employee_id');
    }

    public function replacementEmployee()
    {
        return $this->belongsTo(Employee::class, 'replacement_employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
