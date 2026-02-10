<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    // protected $fillable = ['name', 'code', 'machine_type'];
    protected $fillable = ['branch_id', 'machine_name', 'slug'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function stocks()
    {
        return $this->hasMany(SparepartStock::class);
    }

    public function histories()
    {
        return $this->hasMany(SparepartHistory::class);
    }
}
