<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function scopeOrderByCodeNatural($query, string $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderByRaw("SUBSTRING_INDEX(route_code, '-', 1) {$direction}")
            ->orderByRaw("CAST(SUBSTRING_INDEX(route_code, '-', -1) AS UNSIGNED) {$direction}")
            ->orderBy('route_code', $direction);
    }

    public function routeActions(): HasMany
    {
        return $this->hasMany(RouteAction::class, 'route_id', 'route_id');
    }
}
