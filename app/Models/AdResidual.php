<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdResidual extends Model
{
    protected $table = 'ad_residuals';
    protected $primaryKey = 'ad_residual_id';

    protected $fillable = [
        'branch_id',
        'ad_type',
        'ad_amount',
        'remaining_amount',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
