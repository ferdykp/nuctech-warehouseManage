<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fs6000jkt extends Model
{
    use HasFactory;

    protected $table = 'fs6000jkts';
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
