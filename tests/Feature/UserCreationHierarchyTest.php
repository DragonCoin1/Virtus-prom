<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCreationHierarchyTest extends TestCase
{
    private function createUserWithRole(string $roleName, ?int $cityId = null, ?int $branchId = null): User
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('role_id');
        
        return User::create([
            'user_login' => 'test_' . $roleName . '_' . uniqid(),
            'user_full_name' => 'Test ' . $roleName,
            'role_id' => $roleId,
            'city_id' => $cityId,
            'branch_id' => $branchId,
            'user_password_hash' => Hash::make('password'),
            'password' => Hash::make('password'),
            'user_is_active' => 1,
        ]);
    }

    private function createCityAndBranch(): array
    {
        $city = City::create([
            'city_name' => 'Тестовый город',
            'region_name' => 'Тестовый регион',
            'population' => 500000,
            'is_active' => 1,
        ]);

        $branch = Branch::create([
            'branch_name' => 'Тестовый филиал',
            'city_id' => $city->city_id,
            'is_active' => 1,
        ]);

        return ['city' => $city, 'branch' => $branch];
    }

    public function test_developer_can_create_any_role(): void
    {
        $accessService = app(AccessService::class);
        $developer = $this->createUserWithRole('developer');
        $cityBranch = $this->createCityAndBranch();
        
        $this->assertTrue($accessService->canCreateRole($developer, 'general_director'));
        $this->assertTrue($accessService->canCreateRole($developer, 'regional_director', $cityBranch['city']->city_id));
        $this->assertTrue($accessService->canCreateRole($developer, 'branch_director', null, $cityBranch['branch']->branch_id));
        $this->assertTrue($accessService->canCreateRole($developer, 'manager', null, $cityBranch['branch']->branch_id));
        $this->assertTrue($accessService->canCreateRole($developer, 'promoter', null, $cityBranch['branch']->branch_id));
    }

    public function test_general_director_can_create_any_role(): void
    {
        $accessService = app(AccessService::class);
        $director = $this->createUserWithRole('general_director');
        $cityBranch = $this->createCityAndBranch();
        
        $this->assertTrue($accessService->canCreateRole($director, 'regional_director', $cityBranch['city']->city_id));
        $this->assertTrue($accessService->canCreateRole($director, 'branch_director', null, $cityBranch['branch']->branch_id));
        $this->assertTrue($accessService->canCreateRole($director, 'manager', null, $cityBranch['branch']->branch_id));
    }

    public function test_regional_director_can_create_branch_director_in_same_region(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $regionalDirector = $this->createUserWithRole('regional_director', $cityBranch['city']->city_id);
        
        $this->assertTrue($accessService->canCreateRole($regionalDirector, 'branch_director', null, $cityBranch['branch']->branch_id));
    }

    public function test_regional_director_can_create_manager_in_same_region(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $regionalDirector = $this->createUserWithRole('regional_director', $cityBranch['city']->city_id);
        
        $this->assertTrue($accessService->canCreateRole($regionalDirector, 'manager', null, $cityBranch['branch']->branch_id));
    }

    public function test_regional_director_cannot_create_general_director(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $regionalDirector = $this->createUserWithRole('regional_director', $cityBranch['city']->city_id);
        
        $this->assertFalse($accessService->canCreateRole($regionalDirector, 'general_director'));
    }

    public function test_branch_director_can_create_manager_in_same_branch(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $branchDirector = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canCreateRole($branchDirector, 'manager', null, $cityBranch['branch']->branch_id));
    }

    public function test_branch_director_cannot_create_regional_director(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $branchDirector = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canCreateRole($branchDirector, 'regional_director', $cityBranch['city']->city_id));
    }

    public function test_manager_can_create_promoter_in_same_branch(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canCreateRole($manager, 'promoter', null, $cityBranch['branch']->branch_id));
    }

    public function test_manager_cannot_create_branch_director(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canCreateRole($manager, 'branch_director', null, $cityBranch['branch']->branch_id));
    }

    public function test_promoter_cannot_create_any_role(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $promoter = $this->createUserWithRole('promoter', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canCreateRole($promoter, 'manager', null, $cityBranch['branch']->branch_id));
        $this->assertFalse($accessService->canCreateRole($promoter, 'promoter', null, $cityBranch['branch']->branch_id));
    }
}
