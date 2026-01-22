<?php

namespace App\Http\Controllers;

use App\Models\AdResidual;
use App\Models\Branch;
use App\Services\AccessService;
use Illuminate\Http\Request;

class AdResidualsController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $user = $request->user();
        $branchesQuery = Branch::query()->with('city');
        if ($user) {
            $accessService->scopeBranches($branchesQuery, $user);
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
                        $branchesQuery->where('city_id', $cityId);
                    }
                }
            } else {
                // Developer и General Director - любой город
                $branchesQuery->where('city_id', $cityId);
            }
        }
        
        $branches = $branchesQuery->orderBy('branch_name')->get();
        $branchIds = $branches->pluck('branch_id')->map(fn ($id) => (int) $id)->all();

        $query = AdResidual::query()->with('branch.city');
        if (!empty($branchIds)) {
            $query->whereIn('branch_id', $branchIds);
        } else {
            $query->whereRaw('1=0');
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            if ($user && !$accessService->canAccessBranch($user, $branchId)) {
                abort(403, 'Нет доступа к филиалу');
            }
            $query->where('branch_id', $branchId);
        }

        if ($request->filled('ad_type')) {
            $query->where('ad_type', $request->input('ad_type'));
        }

        if ($request->filled('received_from')) {
            $query->whereDate('received_at', '>=', $request->input('received_from'));
        }

        if ($request->filled('received_to')) {
            $query->whereDate('received_at', '<=', $request->input('received_to'));
        }

        $residuals = $query->orderByDesc('received_at')
            ->orderByDesc('ad_residual_id')
            ->paginate(20)
            ->appends($request->query());
        
        // Рассчитываем актуальные остатки для каждой записи
        foreach ($residuals as $residual) {
            $residual->calculated_remaining = self::calculateRemaining($residual->branch_id, $residual->ad_type);
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

        return view('ad_residuals.index', compact('residuals', 'branches', 'cities', 'user'));
    }

    public function create(AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $user = auth()->user();
        $branchesQuery = Branch::query()->with('city');
        if ($user) {
            $accessService->scopeBranches($branchesQuery, $user);
        }
        $branches = $branchesQuery->orderBy('branch_name')->get();

        return view('ad_residuals.create', compact('branches'));
    }

    public function store(Request $request, AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $data = $this->validateResidual($request);
        $user = $request->user();

        if ($user && !$accessService->canAccessBranch($user, (int) $data['branch_id'])) {
            abort(403, 'Нет доступа к филиалу');
        }

        // При создании прихода остаток равен полученному количеству
        // (расход будет вычитаться автоматически из разноски)
        AdResidual::create([
            'branch_id' => (int) $data['branch_id'],
            'ad_type' => $data['ad_type'],
            'ad_amount' => (int) $data['ad_amount'],
            'remaining_amount' => (int) $data['ad_amount'], // Изначально остаток = получено
            'received_at' => $data['received_at'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('ad_residuals.index')->with('ok', 'Приход рекламы добавлен');
    }

    public function edit(AdResidual $adResidual, AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $user = auth()->user();
        if ($user && !$accessService->canAccessBranch($user, $adResidual->branch_id)) {
            abort(403, 'Нет доступа к филиалу');
        }

        $branchesQuery = Branch::query()->with('city');
        if ($user) {
            $accessService->scopeBranches($branchesQuery, $user);
        }
        $branches = $branchesQuery->orderBy('branch_name')->get();

        return view('ad_residuals.edit', compact('adResidual', 'branches'));
    }

    public function update(Request $request, AdResidual $adResidual, AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $data = $this->validateResidual($request);
        $user = $request->user();

        if ($user && !$accessService->canAccessBranch($user, (int) $data['branch_id'])) {
            abort(403, 'Нет доступа к филиалу');
        }

        $adResidual->update([
            'branch_id' => (int) $data['branch_id'],
            'ad_type' => $data['ad_type'],
            'ad_amount' => (int) $data['ad_amount'],
            'remaining_amount' => (int) $data['ad_amount'], // Обновляем при изменении прихода
            'received_at' => $data['received_at'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('ad_residuals.index')->with('ok', 'Остатки обновлены');
    }

    public function destroy(AdResidual $adResidual, AccessService $accessService)
    {
        $this->assertResidualAccess($accessService);

        $user = auth()->user();
        if ($user && !$accessService->canAccessBranch($user, $adResidual->branch_id)) {
            abort(403, 'Нет доступа к филиалу');
        }

        $adResidual->delete();

        return redirect()->route('ad_residuals.index')->with('ok', 'Остатки удалены');
    }

    private function validateResidual(Request $request): array
    {
        return $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
            'ad_type' => ['required', 'in:листовки,расклейка,визитки'],
            'ad_amount' => ['required', 'integer', 'min:0'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
    }
    
    /**
     * Рассчитывает текущий остаток рекламы на филиале
     * Остаток = сумма всех приходов - сумма всех расходов из разноски
     */
    public static function calculateRemaining(int $branchId, string $adType): int
    {
        // Сумма всех приходов
        $totalReceived = \App\Models\AdResidual::where('branch_id', $branchId)
            ->where('ad_type', $adType)
            ->sum('ad_amount');
        
        // Сумма всех расходов из разноски
        $totalIssued = 0;
        $promoters = \App\Models\Promoter::where('branch_id', $branchId)->pluck('promoter_id');
        
        if ($promoters->isNotEmpty()) {
            $routeActions = \App\Models\RouteAction::whereIn('promoter_id', $promoters)->get();
            
            foreach ($routeActions as $action) {
                if ($adType === 'листовки') {
                    $totalIssued += $action->leaflets_issued ?? 0;
                } elseif ($adType === 'расклейка') {
                    $totalIssued += $action->posters_issued ?? 0;
                } elseif ($adType === 'визитки') {
                    $totalIssued += $action->cards_issued ?? 0;
                }
            }
        }
        
        return max(0, $totalReceived - $totalIssued);
    }

    private function assertResidualAccess(AccessService $accessService): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        if (!$accessService->canManageResiduals($user)) {
            abort(403, 'Нет доступа к остаткам');
        }
    }
}
