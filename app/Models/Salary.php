<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'position',
        'placement',
        'bank',
        'account_no',
        'amount',
        'information',
        'before_after',
        'more_information',
        'get_information',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
