<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\City;
use App\Models\Promoter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        return $this->isBranchDirector($user) || $this->isManager($user) || $this->isPromoter($user);
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

        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            if (!$region) {
                return false;
            }

            return Branch::query()
                ->where('branch_id', $branchId)
                ->whereHas('city', function (Builder $query) use ($region): void {
                    $query->where('region_name', $region);
                })
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

        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            if (!$region) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('branch.city', function (Builder $query) use ($region): void {
                $query->where('region_name', $region);
            });
        }

        if ($this->isBranchScoped($user) && !empty($user->branch_id)) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1=0');
    }

    public function scopeUsers(Builder $query, User $user): Builder
    {
        if ($this->isFullAccess($user)) {
            return $query;
        }

        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            if (!$region) {
                return $query->whereRaw('1=0');
            }

            return $query->where(function (Builder $query) use ($region): void {
                $query->whereHas('city', function (Builder $query) use ($region): void {
                    $query->where('region_name', $region);
                })->orWhereHas('branch.city', function (Builder $query) use ($region): void {
                    $query->where('region_name', $region);
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

        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            if (!$region) {
                return $query->whereRaw('1=0');
            }

            return $query->whereHas('city', function (Builder $query) use ($region): void {
                $query->where('region_name', $region);
            });
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

        if ($this->isRegionalDirector($user)) {
            $region = $this->regionName($user);
            $targetRegion = $this->regionName($target);

            return !empty($region) && !empty($targetRegion) && $region === $targetRegion;
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

            $region = $this->regionName($user);
            if (!$region) {
                return false;
            }

            if ($branchId) {
                return Branch::query()
                    ->where('branch_id', $branchId)
                    ->whereHas('city', function (Builder $query) use ($region): void {
                        $query->where('region_name', $region);
                    })
                    ->exists();
            }

            return City::query()
                ->where('city_id', $cityId)
                ->where('region_name', $region)
                ->exists();
        }

        if ($role === 'branch_director') {
            return $targetRole === 'manager'
                && $this->canAccessBranch($user, $branchId ?? $user->branch_id);
        }

        if ($role === 'manager') {
            return $targetRole === 'promoter'
                && $this->canAccessBranch($user, $branchId ?? $user->branch_id);
        }

        return false;
    }
}
