<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use App\Services\AccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
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

    public function test_manager_cannot_edit_salary(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canEditSalary($manager));
    }

    public function test_branch_director_can_edit_salary(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $director = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canEditSalary($director));
    }

    public function test_developer_can_edit_salary(): void
    {
        $accessService = app(AccessService::class);
        $developer = $this->createUserWithRole('developer');
        
        $this->assertTrue($accessService->canEditSalary($developer));
    }

    public function test_manager_cannot_access_ad_templates(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canManageAdTemplates($manager));
    }

    public function test_branch_director_can_access_ad_templates(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $director = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canManageAdTemplates($director));
    }

    public function test_manager_cannot_access_instructions(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertFalse($accessService->canManageInstructions($manager));
    }

    public function test_general_director_can_access_instructions(): void
    {
        $accessService = app(AccessService::class);
        $director = $this->createUserWithRole('general_director');
        
        $this->assertTrue($accessService->canManageInstructions($director));
    }

    public function test_regional_director_cannot_access_instructions(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $director = $this->createUserWithRole('regional_director', $cityBranch['city']->city_id);
        
        $this->assertFalse($accessService->canManageInstructions($director));
    }

    public function test_promoter_cannot_manage_users(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $promoter = $this->createUserWithRole('promoter', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->isPromoter($promoter));
    }

    public function test_manager_can_access_module_promoters(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canAccessModule($manager, 'promoters', 'view'));
        $this->assertTrue($accessService->canAccessModule($manager, 'promoters', 'edit'));
    }

    public function test_manager_cannot_edit_salary_module(): void
    {
        $accessService = app(AccessService::class);
        $cityBranch = $this->createCityAndBranch();
        
        $manager = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $this->assertTrue($accessService->canAccessModule($manager, 'salary', 'view'));
        $this->assertFalse($accessService->canAccessModule($manager, 'salary', 'edit'));
    }
}
