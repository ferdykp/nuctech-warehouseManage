<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAllowance extends Model
{
    protected $fillable = ['employee_id', 'name', 'amount', 'start_month', 'end_month', 'notes', 'certificate_path'];
    protected $casts = ['amount' => 'decimal:2'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
