<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryHistoryAudit extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['before' => 'array', 'after' => 'array'];
}
