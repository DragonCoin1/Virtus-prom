<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\City;
use App\Models\Promoter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccessService
{
    private array $roleCache = [];
    private array $regionCache = [];

    public function roleName(User $user): ?string
    {
        return $this->roleNameById((int) $user->role_id);
    }

    public function roleNameById(int $roleId): ?string
    {
        if (array_key_exists($roleId, $this->roleCache)) {
            return $this->roleCache[$roleId];
        }

        $name = DB::table('roles')
            ->where('role_id', $roleId)
            ->value('role_name');

        $this->roleCache[$roleId] = $name;

        return $name;
    }

    public function isDeveloper(User $user): bool
    {
        return $this->roleName($user) === 'developer';
    }

    public function isGeneralDirector(User $user): bool
    {
        return $this->roleName($user) === 'general_director';
    }

    public function isRegionalDirector(User $user): bool
    {
        return $this->roleName($user) === 'regional_director';
    }

    public function isBranchDirector(User $user): bool
    {
        return $this->roleName($user) === 'branch_director';
    }

    public function isManager(User $user): bool
    {
        return $this->roleName($user) === 'manager';
    }

    public function isPromoter(User $user): bool
    {
        return $this->roleName($user) === 'promoter';
    }

    public function isFullAccess(User $user): bool
    {
        return $this->isDeveloper($user) || $this->isGeneralDirector($user);
    }

    public function isBranchScoped(User $user): bool
    {
        // Директор больше не считается branch-scoped, так как работает через города
        return $this->isManager($user) || $this->isPromoter($user);
    }

    public function regionName(User $user): ?string
    {
        if (array_key_exists($user->id, $this->regionCache)) {
            return $this->regionCache[$user->id];
        }

        $region = null;

        if (!empty($user->city_id)) {
            $region = City::query()
                ->where('city_id', $user->city_id)
                ->value('region_name');
        } elseif (!empty($user->branch_id)) {
            $region = Branch::query()
                ->where('branch_id', $user->branch_id)
                ->whereHas('city', function (Builder $query): void {
                    $query->whereNotNull('region_name');
                })
                ->with('city')
                ->first()?->city?->region_name;
        }

        $this->regionCache[$user->id] = $region;

        return $region;
    }

    public function canEditSalary(User $user): bool
    {
        return $this->roleName($user) !== 'manager';
    }

    public function canManageAdTemplates(User $user): bool
    {
        return $this->isDeveloper($user)
            || $this->isGeneralDirector($user)
            || $this->isRegionalDirector($user)
            || $this->isBranchDirector($user);
    }

    public function canManageResiduals(User $user): bool
    {
        return $this->isDeveloper($user)
            || $this->isGeneralDirector($user)
            || $this->isRegionalDirector($user)
            || $this->isBranchDirector($user);
    }

    /**
     * UI helper: whether current user can use city filters in modules.
     */
    public function canUseCityFilter(User $user): bool
    {
        return $this->isDeveloper($user)
            || $this->isGeneralDirector($user)
            || $this->isRegionalDirector($user)
            || $this->isBranchDirector($user);
    }

    /**
     * UI helper: cities available for filters (developer/general: all; regional/branch director: own cities).
     */
    public function accessibleCitiesForFilter(User $user): Collection
    {
        if ($this->isDeveloper($user) || $this->isGeneralDirector($user)) {
            return City::query()->orderBy('city_name')->get();
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return collect();
            }
            return City::query()->whereIn('city_id', $cityIds)->orderBy('city_name')->get();
        }

        return collect();
    }

    public function canManageInstructions(User $user): bool
    {
        return $this->isDeveloper($user) || $this->isGeneralDirector($user);
    }

    public function canAccessBranch(User $user, ?int $branchId): bool
    {
        if ($this->isFullAccess($user)) {
            return true;
        }

        if (empty($branchId)) {
            return false;
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return false;
            }

            return Branch::query()
                ->where('branch_id', $branchId)
                ->whereIn('city_id', $cityIds)
                ->exists();
        }

        if ($this->isBranchScoped($user)) {
            return (int) $user->branch_id === (int) $branchId;
        }

        return false;
    }

    public function canAccessPromoter(User $user, Promoter $promoter): bool
    {
        return $this->canAccessBranch($user, $promoter->branch_id);
    }

    public function canAccessPromoterId(User $user, int $promoterId): bool
    {
        $branchId = Promoter::query()
            ->where('promoter_id', $promoterId)
            ->value('branch_id');

        return $this->canAccessBranch($user, $branchId);
    }

    public function scopePromoters(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('branch', function (Builder $query) use ($cityIds): void {
                $query->whereIn('city_id', $cityIds);
            });
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1=0');
    }
    
    /**
     * Получить список city_id для регионального директора или директора филиала
     */
    public function getRegionalDirectorCityIds(User $user): array
    {
        // Если есть связь many-to-many через user_cities, используем её
        if ($user->cities()->exists()) {
            return $user->cities()->get()->pluck('city_id')->toArray();
        }
        
        // Для regional_director используем старую логику через region_name
        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            if (!$region) {
                return [];
            }
            
            return City::query()
                ->where('region_name', $region)
                ->pluck('city_id')
                ->toArray();
        }
        
        // Для branch_director возвращаем пустой массив, если нет связи через user_cities
        return [];
    }
    
    /**
     * Получить список city_id для директора (regional_director или branch_director)
     */
    public function getDirectorCityIds(User $user): array
    {
        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            return $this->getRegionalDirectorCityIds($user);
        }
        return [];
    }

    public function scopeUsers(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->where(function (Builder $query) use ($cityIds): void {
                $query->whereIn('city_id', $cityIds)
                    ->orWhereHas('branch', function (Builder $query) use ($cityIds): void {
                        $query->whereIn('city_id', $cityIds);
                    });
            });
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1=0');
    }

    public function scopeBranches(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->whereIn('city_id', $cityIds);
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1=0');
    }

    public function canAccessUser(User $user, User $target): bool
    {
        if ($this->isFullAccess($user)) {
            return true;
        }

        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $userCityIds = $this->getDirectorCityIds($user);
            if (empty($userCityIds)) {
                return false;
            }
            
            // Проверяем, есть ли у target города из списка user
            if ($target->cities()->exists()) {
                $targetCityIds = $target->cities()->get()->pluck('city_id')->toArray();
                return !empty(array_intersect($userCityIds, $targetCityIds));
            }
            
            // Если у target нет связи через cities, проверяем через city_id
            if (!empty($target->city_id)) {
                return in_array($target->city_id, $userCityIds);
            }
            
            return false;
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return (int) $user->branch_id === (int) $target->branch_id;
        }

        return false;
    }

    public function canAccessModule(User $user, string $module, string $permission = 'view'): bool
    {
        $permission = $permission === 'delete' ? 'edit' : $permission;

        $access = DB::table('role_module_access')
            ->where('role_id', $user->role_id)
            ->where('module_code', $module)
            ->first();

        if (!$access) {
            return false;
        }

        if ($permission === 'edit') {
            return (int) $access->can_edit === 1;
        }

        if ($permission === 'view') {
            return (int) $access->can_view === 1;
        }

        return false;
    }

    public function canCreateRole(User $user, string $targetRole, ?int $cityId = null, ?int $branchId = null): bool
    {
        if ($this->isDeveloper($user)) {
            return true;
        }

        $role = $this->roleName($user);

        if ($role === 'general_director') {
            return true;
        }

        if ($role === 'regional_director') {
            if (!in_array($targetRole, ['manager', 'branch_director'], true)) {
                return false;
            }

            if (!$cityId && !$branchId) {
                return false;
            }

            $userCityIds = $this->getDirectorCityIds($user);
            if (empty($userCityIds)) {
                return false;
            }

            if ($branchId) {
                $branchCityId = Branch::query()
                    ->where('branch_id', $branchId)
                    ->value('city_id');
                return $branchCityId && in_array($branchCityId, $userCityIds);
            }

            return in_array($cityId, $userCityIds);
        }

        if ($role === 'branch_director') {
            // Директор может создавать только менеджеров
            if ($targetRole !== 'manager') {
                return false;
            }
            
            // Если cityId передан, проверяем, что он входит в список городов директора
            if ($cityId) {
                $userCityIds = $this->getDirectorCityIds($user);
                return !empty($userCityIds) && in_array($cityId, $userCityIds);
            }
            
            // Если cityId не передан (например, при фильтрации ролей в форме),
            // разрешаем создание менеджера, если у директора есть хотя бы один город
            $userCityIds = $this->getDirectorCityIds($user);
            return !empty($userCityIds);
        }

        if ($role === 'manager') {
            return $targetRole === 'promoter'
                && $this->canAccessBranch($user, $branchId ?? $user->branch_id);
        }

        return false;
    }

    public function scopeRoutes(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        // Фильтруем routes через route_actions -> promoter -> branch
        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('routeActions.promoter.branch', function (Builder $query) use ($cityIds): void {
                $query->whereIn('city_id', $cityIds);
            });
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->whereHas('routeActions.promoter', function (Builder $query) use ($user): void {
                $query->where('branch_id', $user->branch_id);
            });
        }

        return $query->whereRaw('1=0');
    }

    public function scopeRouteActions(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        // Фильтруем route_actions через promoter -> branch
        if ($this->isRegionalDirector($user) || $this->isBranchDirector($user)) {
            $cityIds = $this->getDirectorCityIds($user);
            if (empty($cityIds)) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('promoter.branch', function (Builder $query) use ($cityIds): void {
                $query->whereIn('city_id', $cityIds);
            });
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->whereHas('promoter', function (Builder $query) use ($user): void {
                $query->where('branch_id', $user->branch_id);
            });
        }

        return $query->whereRaw('1=0');
    }
}
