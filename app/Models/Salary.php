<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'allowance_snapshot',
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

    protected $casts = ['allowance_snapshot' => 'array'];

    public function getAllowanceTotalAttribute(): float
    {
        return array_sum(array_map(fn ($item) => (float) $item['amount'], $this->allowance_snapshot ?? []));
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
