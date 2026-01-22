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

        // Фильтр по городу (для developer, general_director, regional_director)
        $cityId = $request->input('city_id');
        if ($cityId && $user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            // Проверяем доступ к городу
            if ($accessService->isRegionalDirector($user)) {
                $cityIds = $accessService->getRegionalDirectorCityIds($user);
                if (in_array($cityId, $cityIds)) {
                    $usersQuery->where('city_id', $cityId);
                }
            } else {
                // Developer и General Director - любой город
                $usersQuery->where('city_id', $cityId);
            }
        }

        $users = $usersQuery
            ->orderBy('user_full_name')
            ->paginate(20)
            ->appends($request->query());

        // Получаем все роли и фильтруем по правам текущего пользователя
        $allRoles = $this->rolesList();
        $currentUser = $user ?? auth()->user();
        $roles = [];
        
        if ($currentUser) {
            foreach ($allRoles as $roleId => $roleName) {
                // Получаем role_name из базы для проверки
                $roleNameInDb = DB::table('roles')->where('role_id', $roleId)->value('role_name');
                if ($roleNameInDb && $accessService->canCreateRole($currentUser, $roleNameInDb)) {
                    $roles[$roleId] = $roleName;
                }
            }
        } else {
            $roles = $allRoles; // Если пользователь не авторизован, показываем все (но это не должно произойти)
        }
        
        // Передаем canManageUsers явно
        $canManageUsers = $currentUser ? !$accessService->isPromoter($currentUser) : false;

        // Получаем доступные города для фильтра
        $cities = collect();
        if ($user && ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user) || $accessService->isRegionalDirector($user))) {
            if ($accessService->isDeveloper($user) || $accessService->isGeneralDirector($user)) {
                $cities = \App\Models\City::orderBy('city_name')->get();
            } elseif ($accessService->isRegionalDirector($user)) {
                $cityIds = $accessService->getRegionalDirectorCityIds($user);
                if (!empty($cityIds)) {
                    $cities = \App\Models\City::whereIn('city_id', $cityIds)->orderBy('city_name')->get();
                }
            }
        }

        return view('users.index', compact('users', 'roles', 'canManageUsers', 'cities', 'user'));
    }

    public function create(Request $request, AccessService $accessService)
    {
        $user = $request->user();
        
        // Получаем все роли и фильтруем по правам текущего пользователя
        $allRoles = $this->rolesList();
        $roles = [];
        
        if ($user) {
            foreach ($allRoles as $roleId => $roleName) {
                // Получаем role_name из базы для проверки
                $roleNameInDb = DB::table('roles')->where('role_id', $roleId)->value('role_name');
                // При фильтрации ролей в форме cityId еще не выбран, поэтому передаем null
                // canCreateRole должен работать и без cityId для базовой проверки прав
                if ($roleNameInDb && $accessService->canCreateRole($user, $roleNameInDb, null, null)) {
                    $roles[$roleId] = $roleName;
                }
            }
        } else {
            $roles = $allRoles; // Если пользователь не авторизован, показываем все (но это не должно произойти)
        }
        
        // Разработчик видит ВСЕ города (из ТЗ: супер аккаунт)
        // Генеральный директор тоже видит все города
        $citiesQuery = City::query();
        $branchesQuery = Branch::query();
        
        if ($user && $accessService->isDeveloper($user)) {
            // Разработчик - все города и филиалы
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        } elseif ($user && $accessService->isFullAccess($user)) {
            // Генеральный директор - все города и филиалы
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        } else {
            // Остальные - только доступные филиалы и связанные города
            if ($user) {
                $accessService->scopeBranches($branchesQuery, $user);
            }
            $accessibleBranches = $branchesQuery->get();
            $accessibleCityIds = $accessibleBranches->pluck('city_id')->unique()->filter();
            if ($accessibleCityIds->isNotEmpty()) {
                $citiesQuery->whereIn('city_id', $accessibleCityIds);
            } else {
                $citiesQuery->whereRaw('1=0');
            }
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        }

        return view('users.create', compact('roles', 'cities', 'branches', 'user'));
    }

    public function store(Request $request, AccessService $accessService)
    {
        $data = $this->validateUser($request, null);
        $data['city_id'] = $data['city_id'] ?? null;
        $data['branch_id'] = null; // Убираем привязку к филиалу
        
        // Для regional_director и branch_director получаем массив городов
        $cityIds = [];
        if ($request->has('city_ids')) {
            $cityIdsInput = $request->input('city_ids');
            if (is_array($cityIdsInput)) {
                $cityIds = array_filter(array_map('intval', $cityIdsInput));
            } elseif (is_string($cityIdsInput) && !empty($cityIdsInput)) {
                // Если пришла строка с разделителями (запятые)
                $cityIds = array_filter(array_map('intval', explode(',', $cityIdsInput)));
            }
        }

        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $roleName = $accessService->roleNameById((int) $data['role_id']);
        if (!$roleName || !$accessService->canCreateRole($user, $roleName, $data['city_id'] ?? null, null)) {
            abort(403, 'Недостаточно прав для создания этой роли');
        }
        
        // Для regional_director и branch_director проверяем доступ к городам
        if (in_array($roleName, ['regional_director', 'branch_director']) && !empty($cityIds)) {
            foreach ($cityIds as $cityId) {
                if (!$accessService->canCreateRole($user, $roleName, $cityId, null)) {
                    abort(403, 'Нет доступа к одному из выбранных городов');
                }
            }
        }

        $password = Hash::make($data['password']);

        $newUser = User::create([
            'role_id' => $data['role_id'],
            'city_id' => $data['city_id'] ?? null,
            'branch_id' => null, // Убираем привязку к филиалу
            'user_login' => $data['user_login'],
            'user_password_hash' => $password,
            'user_full_name' => $data['user_full_name'],
            'user_is_active' => $data['user_is_active'] ?? true,
            'password' => $password,
        ]);

        // Для regional_director и branch_director сохраняем несколько городов
        if (in_array($roleName, ['regional_director', 'branch_director']) && !empty($cityIds)) {
            $newUser->cities()->sync($cityIds);
        }

        return redirect()->route('users.edit', $newUser)->with('ok', 'Учётная запись создана');
    }

    public function edit(User $user, Request $request, AccessService $accessService)
    {
        $actor = $request->user();
        if (!$actor || !$accessService->canAccessUser($actor, $user)) {
            abort(403, 'Нет доступа к учётной записи');
        }

        $roles = $this->rolesList();
        
        // Разработчик видит ВСЕ города (из ТЗ: супер аккаунт)
        // Генеральный директор тоже видит все города
        $citiesQuery = City::query();
        $branchesQuery = Branch::query();
        
        if ($actor && $accessService->isDeveloper($actor)) {
            // Разработчик - все города и филиалы
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        } elseif ($actor && $accessService->isFullAccess($actor)) {
            // Генеральный директор - все города и филиалы
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        } else {
            // Остальные - только доступные филиалы и связанные города
            if ($actor) {
                $accessService->scopeBranches($branchesQuery, $actor);
            }
            $accessibleBranches = $branchesQuery->get();
            $accessibleCityIds = $accessibleBranches->pluck('city_id')->unique()->filter();
            if ($accessibleCityIds->isNotEmpty()) {
                $citiesQuery->whereIn('city_id', $accessibleCityIds);
            } else {
                $citiesQuery->whereRaw('1=0');
            }
            $cities = $citiesQuery->orderBy('city_name')->get();
            $branches = $branchesQuery->orderBy('branch_name')->get();
        }

        // Получаем выбранные города для regional_director и branch_director
        $selectedCityIds = [];
        if ($accessService->isRegionalDirector($user) || $accessService->isBranchDirector($user)) {
            $selectedCityIds = $user->cities()->get()->pluck('city_id')->toArray();
        }

        return view('users.edit', compact('user', 'roles', 'cities', 'branches', 'selectedCityIds'));
    }

    public function update(User $user, Request $request, AccessService $accessService)
    {
        $actor = $request->user();
        if (!$actor || !$accessService->canAccessUser($actor, $user)) {
            abort(403, 'Нет доступа к учётной записи');
        }

        $data = $this->validateUser($request, $user->id);
        $data['city_id'] = $data['city_id'] ?? null;
        // branch_id всегда null, так как убрали привязку к филиалу
        
        // Для regional_director и branch_director получаем массив городов
        $cityIds = [];
        if ($request->has('city_ids')) {
            $cityIdsInput = $request->input('city_ids');
            if (is_array($cityIdsInput)) {
                $cityIds = array_filter(array_map('intval', $cityIdsInput));
            } elseif (is_string($cityIdsInput) && !empty($cityIdsInput)) {
                // Если пришла строка с разделителями (запятые)
                $cityIds = array_filter(array_map('intval', explode(',', $cityIdsInput)));
            }
        }
        
        $roleName = $accessService->roleNameById((int) $data['role_id']);
        
        // Для regional_director проверяем доступ к городам
        if ($roleName === 'regional_director' && !empty($cityIds)) {
            foreach ($cityIds as $cityId) {
                if (!$accessService->canCreateRole($actor, $roleName, $cityId, null)) {
                    abort(403, 'Нет доступа к одному из выбранных городов');
                }
            }
        }

        $roleName = $accessService->roleNameById((int) $data['role_id']);
        if (!$roleName || !$accessService->canCreateRole($actor, $roleName, $data['city_id'] ?? null, null)) {
            abort(403, 'Недостаточно прав для изменения роли');
        }

        $payload = [
            'role_id' => $data['role_id'],
            'city_id' => $data['city_id'] ?? null,
            'branch_id' => null, // Убираем привязку к филиалу
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

        // Для regional_director и branch_director обновляем несколько городов
        if (in_array($roleName, ['regional_director', 'branch_director'])) {
            if (!empty($cityIds)) {
                $user->cities()->sync($cityIds);
            } else {
                $user->cities()->detach();
            }
        } elseif ($user->cities()->exists()) {
            // Если роль изменилась с regional_director/branch_director на другую, удаляем связи
            $user->cities()->detach();
        }

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
        // Преобразуем city_ids из строки в массив, если нужно
        if ($request->has('city_ids') && is_string($request->input('city_ids'))) {
            $cityIdsString = $request->input('city_ids');
            if (!empty($cityIdsString)) {
                $cityIdsArray = array_filter(array_map('intval', explode(',', $cityIdsString)));
                $request->merge(['city_ids' => $cityIdsArray]);
            } else {
                $request->merge(['city_ids' => []]);
            }
        }
        
        return $request->validate([
            'user_login' => ['required', 'string', 'max:255', 'unique:users,user_login,' . $userId],
            'user_full_name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,role_id'],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:6'],
            'city_id' => ['nullable', 'integer', 'exists:cities,city_id'],
            'city_ids' => ['nullable', 'array'],
            'city_ids.*' => ['integer', 'exists:cities,city_id'],
            'user_is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function rolesList(): array
    {
        $roles = DB::table('roles')
            ->where('role_name', '!=', 'promoter')
            ->orderBy('role_name')
            ->pluck('role_name', 'role_id')
            ->toArray();
        
        // Маппинг ролей на русские названия
        $roleNamesMap = [
            'branch_director' => 'Директор',
            'general_director' => 'Генеральный директор',
            'manager' => 'Менеджер',
            'regional_director' => 'Региональный директор',
            'developer' => 'developer',
        ];
        
        // Применяем маппинг к названиям ролей
        $result = [];
        foreach ($roles as $roleId => $roleName) {
            $result[$roleId] = $roleNamesMap[$roleName] ?? $roleName;
        }
        
        return $result;
    }
}
