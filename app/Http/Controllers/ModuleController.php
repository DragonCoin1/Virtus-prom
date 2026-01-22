<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleController extends Controller
{
    public function cards(Request $request, \App\Services\AccessService $accessService)
    {
        $user = $request->user();
        
        // Менеджеры видят только маршруты своего города через branch_id
        // Директора видят маршруты своих городов через user_cities
        $cityFilter = null;
        if ($user && $accessService->isManager($user) && !empty($user->branch_id)) {
            $cityId = \App\Models\Branch::where('branch_id', $user->branch_id)->value('city_id');
            if ($cityId) {
                $cityFilter = $cityId;
            }
        } elseif ($user && $accessService->isBranchDirector($user)) {
            // Для директора берем первый город из списка, если не выбран фильтр
            $cityIds = $accessService->getDirectorCityIds($user);
            if (!empty($cityIds) && !$request->input('city_id')) {
                $cityFilter = $cityIds[0]; // Показываем первый город по умолчанию
            }
        }

        // Фильтр по городу из запроса (для developer, general_director, regional_director, branch_director)
        $cityId = $request->input('city_id');
        if ($cityId && $user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user))) {
            // Проверяем доступ к городу
            if ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
                $cityIds = $accessService->getDirectorCityIds($user);
                if (in_array($cityId, $cityIds)) {
                    $cityFilter = $cityId;
                }
            } else {
                // Developer и General Director - любой город
                $cityFilter = $cityId;
            }
        }
        
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
            ]);

        // Фильтрация для менеджеров - только их город
        if ($cityFilter !== null) {
            $hasCityId = \Illuminate\Support\Facades\Schema::hasColumn('routes', 'city_id');
            if ($hasCityId) {
                $q->where('r.city_id', $cityFilter);
            } else {
                // Если столбца еще нет, фильтруем через route_actions -> promoter -> branch
                $q->whereExists(function ($query) use ($cityFilter) {
                    $query->select(DB::raw(1))
                        ->from('route_actions as ra_filter')
                        ->join('promoters as p_filter', 'ra_filter.promoter_id', '=', 'p_filter.promoter_id')
                        ->join('branches as b_filter', 'p_filter.branch_id', '=', 'b_filter.branch_id')
                        ->whereColumn('ra_filter.route_id', 'r.route_id')
                        ->where('b_filter.city_id', $cityFilter);
                });
            }
        }

        $q->orderByRaw('ra.last_action_date IS NULL DESC')
            ->orderBy('ra.last_action_date', 'asc');

        if (Schema::hasColumn('routes', 'sort_order')) {
            $q->orderBy('r.sort_order', 'asc');
        }

        $q->orderByRaw("SUBSTRING_INDEX(r.route_code, '-', 1) asc")
            ->orderByRaw("CAST(SUBSTRING_INDEX(r.route_code, '-', -1) AS UNSIGNED) asc")
            ->orderBy('r.route_code', 'asc');

        $routes = $q->paginate(80)->withQueryString();

        // Получаем доступные города для фильтра
        $cities = collect();
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user))) {
            if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
                $cities = \App\Models\City::orderBy('city_name')->get();
            } elseif ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
                $cityIds = $accessService->getDirectorCityIds($user);
                if (!empty($cityIds)) {
                    $cities = \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
                }
            }
        }

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

        return view('modules.cards', compact('routes', 'cities', 'user'));
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
