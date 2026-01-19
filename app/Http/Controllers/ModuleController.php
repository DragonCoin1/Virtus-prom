<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleController extends Controller
{
    public function cards(Request $request)
    {
        $lastActionsSub = DB::table('route_actions')
            ->select('route_id', DB::raw('MAX(action_date) as last_action_date'))
            ->groupBy('route_id');

        $q = DB::table('routes as r')
            ->leftJoinSub($lastActionsSub, 'ra', function ($join) {
                $join->on('r.route_id', '=', 'ra.route_id');
            })
            ->select([
                'r.route_id',
                'r.route_code',
                'r.route_district',
                'r.route_type',
                'r.boxes_count',
                'r.entrances_count',
                'ra.last_action_date',
            ])
            ->orderByRaw('ra.last_action_date IS NULL DESC')
            ->orderBy('ra.last_action_date', 'asc');

        if (Schema::hasColumn('routes', 'sort_order')) {
            $q->orderBy('r.sort_order', 'asc');
        }

        $q->orderByRaw("SUBSTRING_INDEX(r.route_code, '-', 1) asc")
            ->orderByRaw("CAST(SUBSTRING_INDEX(r.route_code, '-', -1) AS UNSIGNED) asc")
            ->orderBy('r.route_code', 'asc');

        $routes = $q->paginate(80)->withQueryString();

        $now = Carbon::now();

        $routes->getCollection()->transform(function ($route) use ($now) {
            $route->last_action_date_parsed = $route->last_action_date
                ? Carbon::parse($route->last_action_date)
                : null;

            if (!$route->last_action_date_parsed) {
                $route->age_label = '—';
                $route->is_stale = true;
                return $route;
            }

            $route->age_label = $route->last_action_date_parsed->diffForHumans(
                $now,
                [
                    'parts' => 1,
                    'short' => true,
                    'syntax' => Carbon::DIFF_ABSOLUTE,
                ]
            );

            $route->is_stale = $route->last_action_date_parsed->diffInDays($now) > 7;

            return $route;
        });

        return view('modules.cards', compact('routes'));
    }

    public function interviews()
    {
        return view('modules.interviews');
    }

    public function salary()
    {
        return view('modules.salary');
    }

    public function keysRegistry()
    {
        return view('modules.keys_registry');
    }

    public function reports()
    {
        return view('modules.reports');
    }
}
