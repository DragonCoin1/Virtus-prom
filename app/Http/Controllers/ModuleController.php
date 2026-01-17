<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'r.*',
                'ra.last_action_date',
            ]);

        $q->orderByRaw('ra.last_action_date IS NULL DESC')
          ->orderBy('ra.last_action_date', 'asc')
          ->orderBy('r.route_code', 'asc');

        $routes = $q->paginate(80);

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
