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

        return view('ad_residuals.index', compact('residuals', 'branches'));
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

        AdResidual::create([
            'branch_id' => (int) $data['branch_id'],
            'ad_type' => $data['ad_type'],
            'ad_amount' => (int) $data['ad_amount'],
            'remaining_amount' => (int) $data['remaining_amount'],
            'received_at' => $data['received_at'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('ad_residuals.index')->with('ok', 'Остатки добавлены');
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
            'remaining_amount' => (int) $data['remaining_amount'],
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
            'ad_type' => ['required', 'string', 'max:50'],
            'ad_amount' => ['required', 'integer', 'min:0'],
            'remaining_amount' => ['required', 'integer', 'min:0'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
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
