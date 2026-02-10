<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['branch_name', 'branch_code', 'branch_address'];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
