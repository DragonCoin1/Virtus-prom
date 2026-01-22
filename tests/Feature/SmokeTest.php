<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    private function createUserWithRole(string $roleName, ?int $cityId = null, ?int $branchId = null): User
    {
        $roleId = DB::table('roles')->where('role_name', $roleName)->value('role_id');
        
        return User::create([
            'user_login' => 'test_' . $roleName,
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

    public function test_user_can_login(): void
    {
        $user = $this->createUserWithRole('manager');
        
        $response = $this->post('/login', [
            'user_login' => $user->user_login,
            'password' => 'password',
        ]);
        
        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_home(): void
    {
        $user = $this->createUserWithRole('manager');
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertStatus(302); // Redirect
    }

    public function test_manager_can_access_promoters_module(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/promoters');
        
        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_salary_edit(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/salary/adjustments/create');
        
        $response->assertStatus(403);
    }

    public function test_branch_director_can_access_salary_edit(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/salary/adjustments/create');
        
        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_ad_templates(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/ad-templates');
        
        $response->assertStatus(403);
    }

    public function test_branch_director_can_access_ad_templates(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('branch_director', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/ad-templates');
        
        $response->assertStatus(200);
    }

    public function test_promoter_cannot_access_users_management(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('promoter', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/users');
        
        $response->assertStatus(403);
    }

    public function test_manager_can_access_users_management(): void
    {
        $cityBranch = $this->createCityAndBranch();
        $user = $this->createUserWithRole('manager', $cityBranch['city']->city_id, $cityBranch['branch']->branch_id);
        
        $response = $this->actingAs($user)->get('/users');
        
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_protected_routes(): void
    {
        $response = $this->get('/promoters');
        
        $response->assertRedirect('/login');
    }

    public function test_cities_index_is_accessible(): void
    {
        $user = $this->createUserWithRole('manager');
        
        $response = $this->actingAs($user)->get('/cities');
        
        $response->assertStatus(200);
    }

    public function test_cities_import_form_is_accessible(): void
    {
        $user = $this->createUserWithRole('manager');
        
        $response = $this->actingAs($user)->get('/cities/import');
        
        $response->assertStatus(200);
    }
}
