<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'city_id';

    protected $fillable = [
        'city_name',
        'region_name',
        'population',
        'is_active',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'city_id', 'city_id');
    }
}
