<?php

use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('može da kreira facility', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($admin);

    $response = $this->postJson('/api/v1/facilities', [
        'name' => 'Test Facility',
        'address' => 'Test Address',
        'city' => 'Test City',
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'name', 'address', 'city']);
});

it('može da izmeni facility', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($admin);
    $facility = \App\Models\Facility::factory()->create(['company_id' => $company->id]);

    $response = $this->patchJson('/api/v1/facilities/' . $facility->id, [
        'name' => 'Izmenjena Facility',
        'address' => 'Nova Adresa',
        'city' => 'Novi Grad',
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Izmenjena Facility', 'address' => 'Nova Adresa', 'city' => 'Novi Grad']);
});

it('može da obriše facility', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $admin = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($admin);
    $facility = \App\Models\Facility::factory()->create(['company_id' => $company->id]);

    $response = $this->deleteJson('/api/v1/facilities/' . $facility->id, [], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['message' => 'Facility deleted']);
});
