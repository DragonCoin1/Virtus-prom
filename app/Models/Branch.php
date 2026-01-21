<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $table = 'branches';
    protected $primaryKey = 'branch_id';

    protected $fillable = [
        'branch_name',
        'city_id',
        'is_active',
        'notes',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function promoters(): HasMany
    {
        return $this->hasMany(Promoter::class, 'branch_id', 'branch_id');
    }
}
