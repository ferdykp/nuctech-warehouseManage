<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'is_overtime_cover',
        'covered_employee_id',
        'shift_replacement_request_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d', // <-- Wajib ada agar format konsisten Y-m-d
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
