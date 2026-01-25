<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdjustment extends Model
{
    protected $table = 'salary_adjustments';
    protected $primaryKey = 'salary_adjustment_id';

    protected $fillable = [
        'promoter_id',
        'city_id',
        'adj_date',
        'amount',
        'comment',
        'created_by',
    ];

    public function promoter()
    {
        return $this->belongsTo(Promoter::class, 'promoter_id', 'promoter_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }
}
