<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promoter extends Model
{
    protected $table = 'promoters';
    protected $primaryKey = 'promoter_id';

    protected $fillable = [
        'promoter_full_name',
        'promoter_phone',
        'promoter_requisites',
        'promoter_status',
        'hired_at',
        'fired_at',
        'promoter_comment',
    ];
}
