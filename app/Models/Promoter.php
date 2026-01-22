<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promoter extends Model
{
    protected $table = 'promoters';
    protected $primaryKey = 'promoter_id';

    protected $fillable = [
        'branch_id',
        'user_id',
        'promoter_full_name',
        'promoter_phone',
        'promoter_requisites',
        'promoter_status',
        'hired_at',
        'fired_at',
        'promoter_comment',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
