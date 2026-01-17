<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdTemplate extends Model
{
    protected $table = 'ad_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'template_name',
        'template_type',
        'is_active',
    ];
}
