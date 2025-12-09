<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
