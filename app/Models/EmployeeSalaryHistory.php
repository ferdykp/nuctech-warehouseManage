<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancelled_at',
        'employee_id',
        'old_salary',
        'new_salary',
        'reason',
        'updated_by',
    ];

    protected $casts = ['cancelled_at' => 'datetime', 'old_salary' => 'decimal:2', 'new_salary' => 'decimal:2'];

    public function audits()
    {
        return $this->hasMany(SalaryHistoryAudit::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
