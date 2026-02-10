<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'attendant',
        'site_machine',
        'series_machine',
        'failure_date',
        'failure_note',
        'ts_procedure',
        'image'
    ];
}
