<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteAction extends Model
{
    protected $table = 'route_actions';
    protected $primaryKey = 'route_action_id';

    protected $fillable = [
        'action_date',
        'promoter_id',
        'route_id',

        'leaflets_total',
        'leaflets_issued',

        'posters_total',
        'posters_issued',

        'poster_variant',

        'cards_count',
        'cards_issued',

        'boxes_done',

        'payment_amount',

        'action_comment',
        'created_by',
    ];

    public function promoter()
    {
        return $this->belongsTo(Promoter::class, 'promoter_id', 'promoter_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function branch()
    {
        return $this->hasOneThrough(
            Branch::class,
            Promoter::class,
            'promoter_id',
            'branch_id',
            'promoter_id',
            'branch_id'
        );
    }

    public function templates()
    {
        return $this->belongsToMany(
            AdTemplate::class,
            'route_action_templates',
            'route_action_id',
            'template_id'
        )->withTimestamps();
    }
}
