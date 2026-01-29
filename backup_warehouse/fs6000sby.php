<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class fs6000sby extends Model
{
    use HasFactory;

    protected $table = 'fs6000sbys';
    protected $fillable = [
        'item_name',
        'type',
        'stock',
        'uom',
        'date_update',
        'location',
        'note',
        'image'
    ];
}
