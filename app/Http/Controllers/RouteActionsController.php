<?php

namespace App\Http\Controllers;

use App\Models\AdTemplate;
use App\Models\Promoter;
use App\Models\Route;
use App\Models\RouteAction;
use App\Services\AccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RouteActionsController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $promotersQuery = Promoter::orderBy('promoter_full_name');
        $user = $request->user();
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        $routesQuery = Route::query();
        if (Schema::hasColumn('routes', 'sort_order')) {
            $routesQuery->orderBy('sort_order');
        }
        $routes = $routesQuery->orderByCodeNatural()->get();

        $q = RouteAction::query()->with(['promoter', 'route', 'createdBy', 'templates']);
        if ($user) {
            $accessService->scopeRouteActions($q, $user);
        }

        // Фильтр по городу (для developer, general_director, regional_director)
        $cityId = $request->input('city_id');
        if ($cityId && $user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            // Проверяем доступ к городу
            if ($accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $cityExists = \App\Models\City::where('city_id', $cityId)
                        ->where('region_name', $region)
                        ->exists();
                    if ($cityExists) {
                        $q->whereHas('promoter.branch', function ($query) use ($cityId) {
                            $query->where('city_id', $cityId);
                        });
                    }
                }
            } else {
                // Developer и General Director - любой город
                $q->whereHas('promoter.branch', function ($query) use ($cityId) {
                    $query->where('city_id', $cityId);
                });
            }
        }

        $hasFilters = false;

        if ($request->filled('date_from')) {
            $q->whereDate('action_date', '>=', $request->input('date_from'));
            $hasFilters = true;
        }

        if ($request->filled('date_to')) {
            $q->whereDate('action_date', '<=', $request->input('date_to'));
            $hasFilters = true;
        }

        if ($request->filled('promoter_id')) {
            $q->where('promoter_id', (int)$request->input('promoter_id'));
            $hasFilters = true;
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $q->whereHas('promoter', function ($query) use ($status) {
                $query->where('promoter_status', $status);
            });
            $hasFilters = true;
        }

        if ($request->filled('route_id')) {
            $q->where('route_id', (int)$request->input('route_id'));
            $hasFilters = true;
        }

        $sumPayment = null;
        if ($hasFilters) {
            $sumPayment = (clone $q)->sum('payment_amount');
        }

        $actions = $q->orderByDesc('action_date')
            ->orderByDesc('route_action_id')
            ->paginate(20)
            ->appends($request->query());

        $canEdit = false;
        if (auth()->check()) {
            $canEdit = (int)DB::table('role_module_access')
                ->where('role_id', auth()->user()->role_id)
                ->where('module_code', 'route_actions')
                ->value('can_edit') === 1;
        }

        // Получаем доступные города для фильтра
        $cities = collect();
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
                $cities = \App\Models\City::orderBy('city_name')->get();
            } elseif ($accessService->isRegionalDirector($user)) {
                $region = $accessService->regionName($user);
                if ($region) {
                    $cities = \App\Models\City::where('region_name', $region)->orderBy('city_name')->get();
                }
            }
        }

        return view('route_actions.index', compact(
            'actions',
            'promoters',
            'routes',
            'sumPayment',
            'hasFilters',
            'canEdit',
            'cities',
            'user'
        ));
    }

    public function create(AccessService $accessService)
    {
        $promotersQuery = Promoter::orderBy('promoter_full_name');
        $user = auth()->user();
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        $routesQuery = Route::query();
        if (Schema::hasColumn('routes', 'sort_order')) {
            $routesQuery->orderBy('sort_order');
        }
        $routes = $routesQuery->orderByCodeNatural()->get();

        $leafletTemplates = AdTemplate::where('template_type', 'leaflet')
            ->where('is_active', 1)
            ->orderBy('template_name')
            ->get();

        return view('route_actions.create', compact('promoters', 'routes', 'leafletTemplates'));
    }

    public function store(Request $request, AccessService $accessService)
    {
        $data = $this->validateAction($request);
        $user = $request->user();
        if ($user && !$accessService->canAccessPromoterId($user, (int) $data['promoter_id'])) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $action = RouteAction::create([
            'action_date' => $data['action_date'],
            'promoter_id' => $data['promoter_id'],
            'route_id' => $data['route_id'],

            'leaflets_total' => $data['leaflets_total'] ?? 0,
            'leaflets_issued' => $data['leaflets_issued'] ?? 0,

            'posters_total' => $data['posters_total'] ?? 0,
            'posters_issued' => $data['posters_issued'] ?? 0,

            'poster_variant' => null,

            'cards_count' => $data['cards_count'] ?? 0,
            'cards_issued' => $data['cards_issued'] ?? 0,

            'boxes_done' => $data['boxes_done'] ?? 0,

            'payment_amount' => $data['payment_amount'] ?? 0,
            'action_comment' => $data['action_comment'] ?? null,

            'created_by' => auth()->id(),
        ]);

        $ids = $data['leaflet_template_ids'] ?? [];
        $ids = array_values(array_unique($ids));

        $ids = AdTemplate::where('template_type', 'leaflet')
            ->where('is_active', 1)
            ->whereIn('template_id', $ids)
            ->pluck('template_id')
            ->toArray();

        $action->templates()->sync($ids);

        return redirect()->route('module.route_actions')->with('ok', 'Запись разноски добавлена');
    }

    public function edit(RouteAction $routeAction, AccessService $accessService)
    {
        $routeAction->load(['templates', 'promoter']);
        $user = auth()->user();
        if ($user && !$accessService->canAccessPromoter($user, $routeAction->promoter)) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $promotersQuery = Promoter::orderBy('promoter_full_name');
        if ($user) {
            $accessService->scopePromoters($promotersQuery, $user);
        }
        $promoters = $promotersQuery->get();
        $routesQuery = Route::query();
        if (Schema::hasColumn('routes', 'sort_order')) {
            $routesQuery->orderBy('sort_order');
        }
        $routes = $routesQuery->orderByCodeNatural()->get();

        $selectedIds = $routeAction->templates->pluck('template_id')->toArray();

        $leafletTemplates = AdTemplate::where('template_type', 'leaflet')
            ->where(function ($q) use ($selectedIds) {
                $q->where('is_active', 1);
                if (!empty($selectedIds)) $q->orWhereIn('template_id', $selectedIds);
            })
            ->orderByDesc('is_active')
            ->orderBy('template_name')
            ->get();

        return view('route_actions.edit', compact('routeAction', 'promoters', 'routes', 'leafletTemplates'));
    }

    public function update(Request $request, RouteAction $routeAction, AccessService $accessService)
    {
        $data = $this->validateAction($request);
        $user = $request->user();
        if ($user && !$accessService->canAccessPromoterId($user, (int) $data['promoter_id'])) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $routeAction->update([
            'action_date' => $data['action_date'],
            'promoter_id' => $data['promoter_id'],
            'route_id' => $data['route_id'],

            'leaflets_total' => $data['leaflets_total'] ?? 0,
            'leaflets_issued' => $data['leaflets_issued'] ?? 0,

            'posters_total' => $data['posters_total'] ?? 0,
            'posters_issued' => $data['posters_issued'] ?? 0,

            'cards_count' => $data['cards_count'] ?? 0,
            'cards_issued' => $data['cards_issued'] ?? 0,

            'boxes_done' => $data['boxes_done'] ?? 0,

            'payment_amount' => $data['payment_amount'] ?? 0,
            'action_comment' => $data['action_comment'] ?? null,
        ]);

        $ids = $data['leaflet_template_ids'] ?? [];
        $ids = array_values(array_unique($ids));

        $ids = AdTemplate::where('template_type', 'leaflet')
            ->where('is_active', 1)
            ->whereIn('template_id', $ids)
            ->pluck('template_id')
            ->toArray();

        $routeAction->templates()->sync($ids);

        return redirect()->route('module.route_actions')->with('ok', 'Запись разноски обновлена');
    }

    public function destroy(RouteAction $routeAction, AccessService $accessService)
    {
        $routeAction->load('promoter');
        $user = auth()->user();
        if ($user && !$accessService->canAccessPromoter($user, $routeAction->promoter)) {
            abort(403, 'Нет доступа к промоутеру');
        }

        $routeAction->templates()->detach();
        $routeAction->delete();

        return redirect()->route('module.route_actions')->with('ok', 'Запись разноски удалена');
    }

    private function validateAction(Request $request): array
    {
        return $request->validate([
            'action_date' => ['required', 'date'],
            'promoter_id' => ['required', 'integer', 'exists:promoters,promoter_id'],
            'route_id' => ['required', 'integer', 'exists:routes,route_id'],

            'leaflets_total' => ['nullable', 'integer', 'min:0'],
            'leaflets_issued' => ['nullable', 'integer', 'min:0'],

            'posters_total' => ['nullable', 'integer', 'min:0'],
            'posters_issued' => ['nullable', 'integer', 'min:0'],

            'cards_count' => ['nullable', 'integer', 'min:0'],
            'cards_issued' => ['nullable', 'integer', 'min:0'],

            'boxes_done' => ['nullable', 'integer', 'min:0'],
            'payment_amount' => ['nullable', 'integer', 'min:0'],

            'action_comment' => ['nullable', 'string', 'max:255'],

            'leaflet_template_ids' => ['nullable', 'array'],
            'leaflet_template_ids.*' => [
                'integer',
                Rule::exists('ad_templates', 'template_id')
                    ->where('template_type', 'leaflet')
                    ->where('is_active', 1),
            ],
        ]);
    }
}
