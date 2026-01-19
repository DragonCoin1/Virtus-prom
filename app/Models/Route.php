<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'routes';
    protected $primaryKey = 'route_id';

    protected $fillable = [
        'route_code',
        'route_district',
        'route_type',
        'is_active',
        'boxes_count',
        'entrances_count',
        'route_comment',
        'sort_order',
    ];
}
