<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = ['name', 'code', 'machine_type'];

    public function stocks()
    {
        return $this->hasMany(SparepartStock::class);
    }
}
