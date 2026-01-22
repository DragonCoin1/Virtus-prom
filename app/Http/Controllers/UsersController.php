<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request, AccessService $accessService)
    {
        $usersQuery = User::query()->with(['city', 'branch']);
        $user = $request->user();
        if ($user) {
            $accessService->scopeUsers($usersQuery, $user);
        }

        $users = $usersQuery
            ->orderBy('user_full_name')
            ->paginate(20)
            ->appends($request->query());

        $roles = $this->rolesList();
        
        // Передаем canManageUsers явно
        $currentUser = $user ?? auth()->user();
        $canManageUsers = $currentUser ? !$accessService->isPromoter($currentUser) : false;

        return view('users.index', compact('users', 'roles', 'canManageUsers'));
    }

    public function create(Request $request, AccessService $accessService)
    {
        $user = $request->user();
        $roles = $this->rolesList();
        
        // Разработчик и генеральный директор видят все города и филиалы
        $citiesQuery = City::query();
        $branchesQuery = Branch::query();
        
        if ($user && !$accessService->isFullAccess($user)) {
            $accessService->scopeBranches($branchesQuery, $user);
            // Для городов используем те, что связаны с доступными филиалами
            $accessibleBranches = $branchesQuery->get();
            $accessibleCityIds = $accessibleBranches->pluck('city_id')->unique()->filter();
            if ($accessibleCityIds->isNotEmpty()) {
                $citiesQuery->whereIn('city_id', $accessibleCityIds);
            } else {
                $citiesQuery->whereRaw('1=0');
            }
        }
        
        $cities = $citiesQuery->orderBy('city_name')->get();
        $branches = $branchesQuery->orderBy('branch_name')->get();

        return view('users.create', compact('roles', 'cities', 'branches', 'user'));
    }

    public function store(Request $request, AccessService $accessService)
    {
        $data = $this->validateUser($request, null);
        $data['city_id'] = $data['city_id'] ?: null;
        $data['branch_id'] = $data['branch_id'] ?: null;

        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $roleName = $accessService->roleNameById((int) $data['role_id']);
        if (!$roleName || !$accessService->canCreateRole($user, $roleName, $data['city_id'], $data['branch_id'])) {
            abort(403, 'Недостаточно прав для создания этой роли');
        }

        $password = Hash::make($data['password']);

        $newUser = User::create([
            'role_id' => $data['role_id'],
            'city_id' => $data['city_id'],
            'branch_id' => $data['branch_id'],
            'user_login' => $data['user_login'],
            'user_password_hash' => $password,
            'user_full_name' => $data['user_full_name'],
            'user_is_active' => $data['user_is_active'] ?? true,
            'password' => $password,
        ]);

        return redirect()->route('users.edit', $newUser)->with('ok', 'Учётная запись создана');
    }

    public function edit(User $user, Request $request, AccessService $accessService)
    {
        $actor = $request->user();
        if (!$actor || !$accessService->canAccessUser($actor, $user)) {
            abort(403, 'Нет доступа к учётной записи');
        }

        $roles = $this->rolesList();
        
        // Разработчик и генеральный директор видят все города и филиалы
        $citiesQuery = City::query();
        $branchesQuery = Branch::query();
        
        if ($actor && !$accessService->isFullAccess($actor)) {
            $accessService->scopeBranches($branchesQuery, $actor);
            // Для городов используем те, что связаны с доступными филиалами
            $accessibleBranches = $branchesQuery->get();
            $accessibleCityIds = $accessibleBranches->pluck('city_id')->unique()->filter();
            if ($accessibleCityIds->isNotEmpty()) {
                $citiesQuery->whereIn('city_id', $accessibleCityIds);
            } else {
                $citiesQuery->whereRaw('1=0');
            }
        }
        
        $cities = $citiesQuery->orderBy('city_name')->get();
        $branches = $branchesQuery->orderBy('branch_name')->get();

        return view('users.edit', compact('user', 'roles', 'cities', 'branches'));
    }

    public function update(User $user, Request $request, AccessService $accessService)
    {
        $actor = $request->user();
        if (!$actor || !$accessService->canAccessUser($actor, $user)) {
            abort(403, 'Нет доступа к учётной записи');
        }

        $data = $this->validateUser($request, $user->id);
        $data['city_id'] = $data['city_id'] ?: null;
        $data['branch_id'] = $data['branch_id'] ?: null;

        $roleName = $accessService->roleNameById((int) $data['role_id']);
        if (!$roleName || !$accessService->canCreateRole($actor, $roleName, $data['city_id'], $data['branch_id'])) {
            abort(403, 'Недостаточно прав для изменения роли');
        }

        $payload = [
            'role_id' => $data['role_id'],
            'city_id' => $data['city_id'],
            'branch_id' => $data['branch_id'],
            'user_login' => $data['user_login'],
            'user_full_name' => $data['user_full_name'],
            'user_is_active' => $data['user_is_active'] ?? true,
        ];

        if (!empty($data['password'])) {
            $password = Hash::make($data['password']);
            $payload['user_password_hash'] = $password;
            $payload['password'] = $password;
        }

        $user->update($payload);

        return redirect()->route('users.edit', $user)->with('ok', 'Учётная запись обновлена');
    }

    public function destroy(User $user, Request $request, AccessService $accessService)
    {
        $actor = $request->user();
        if (!$actor || !$accessService->canAccessUser($actor, $user)) {
            abort(403, 'Нет доступа к учётной записи');
        }

        $user->delete();

        return redirect()->route('users.index')->with('ok', 'Учётная запись удалена');
    }

    private function validateUser(Request $request, ?int $userId): array
    {
        return $request->validate([
            'user_login' => ['required', 'string', 'max:255', 'unique:users,user_login,' . $userId],
            'user_full_name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:6'],
            'city_id' => ['nullable', 'integer', 'exists:cities,city_id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,branch_id'],
            'user_is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function rolesList(): array
    {
        return DB::table('roles')
            ->orderBy('role_name')
            ->pluck('role_name', 'role_id')
            ->toArray();
    }
}
