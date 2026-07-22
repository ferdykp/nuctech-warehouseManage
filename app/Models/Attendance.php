<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    // Tambahkan 'matrix_details' ke dalam array mass-assignable ini
    protected $fillable = [
        'employee_id',
        'month',
        'working_days',
        'attendance_count',
        'matrix_details'
    ];

    /**
     * Relasi balik ke Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
