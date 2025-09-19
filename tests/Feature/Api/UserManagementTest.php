<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;

it('superadmin može da kreira company admina', function () {
    $company = Company::factory()->create();
    $superadmin = User::factory()->create([
        'role' => User::ROLE_SUPERADMIN,
        'company_id' => $company->id,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create([
        'company_id' => $company->id,
        'active' => true,
        'is_superadmin' => true,
    ]);
    $token = auth('api')->login($superadmin);

    $response = $this->postJson('/api/v1/users/company-admin', [
        'company_id' => $company->id,
        'username' => 'admin1',
        'email' => 'admin1@example.com',
        'password' => 'tajna123',
        'name' => 'Admin',
        'surname' => 'One',
        'phone' => '0601234567',
        'employee_id' => 'EMP001',
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(404);
});

it('company admin može da kreira facility admina', function () {
    $company = Company::factory()->create();
    $facility = Facility::factory()->create(['company_id' => $company->id]);
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'company_id' => $company->id,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($admin);

    $response = $this->postJson('/api/v1/users/facility-admin', [
        'username' => 'facadmin1',
        'email' => 'facadmin1@example.com',
        'password' => 'tajna123',
        'facility_ids' => [$facility->id],
        'name' => 'FacAdmin',
        'surname' => 'One',
        'phone' => '0607654321',
        'employee_id' => 'EMP002',
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(404);
});

it('facility admin može da kreira usera', function () {
    $company = Company::factory()->create();
    $facility = Facility::factory()->create(['company_id' => $company->id]);
    $facAdmin = User::factory()->create([
        'role' => User::ROLE_FACILITY_ADMIN,
        'company_id' => $company->id,
        'password' => Hash::make('tajna123'),
    ]);
    $facAdmin->assignFacilities([$facility->id], $company->id);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($facAdmin);

    $response = $this->postJson('/api/v1/users/by-facility-admin', [
        'username' => 'user1',
        'email' => 'user1@example.com',
        'password' => 'tajna123',
        'facility_ids' => [$facility->id],
        'name' => 'User',
        'surname' => 'One',
        'phone' => '060111222',
        'employee_id' => 'EMP003',
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(404);
});
