<?php

namespace App\Http\Controllers;

use App\Models\AdTemplate;
use App\Models\Promoter;
use App\Models\Route;
use App\Models\RouteAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RouteActionsController extends Controller
{
    public function index(Request $request)
    {
        $promoters = Promoter::orderBy('promoter_full_name')->get();
        $routes = Route::orderBy('sort_order')->orderBy('route_code')->get();

        $q = RouteAction::query()->with(['promoter', 'route', 'createdBy', 'templates']);

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

        return view('route_actions.index', compact(
            'actions',
            'promoters',
            'routes',
            'sumPayment',
            'hasFilters',
            'canEdit'
        ));
    }

    public function create()
    {
        $promoters = Promoter::orderBy('promoter_full_name')->get();
        $routes = Route::orderBy('sort_order')->orderBy('route_code')->get();

        $leafletTemplates = AdTemplate::where('template_type', 'leaflet')
            ->where('is_active', 1)
            ->orderBy('template_name')
            ->get();

        return view('route_actions.create', compact('promoters', 'routes', 'leafletTemplates'));
    }

    public function store(Request $request)
    {
        $data = $this->validateAction($request);

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

    public function edit(RouteAction $routeAction)
    {
        $routeAction->load(['templates']);

        $promoters = Promoter::orderBy('promoter_full_name')->get();
        $routes = Route::orderBy('sort_order')->orderBy('route_code')->get();

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

    public function update(Request $request, RouteAction $routeAction)
    {
        $data = $this->validateAction($request);

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

    public function destroy(RouteAction $routeAction)
    {
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
