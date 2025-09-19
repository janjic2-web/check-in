<?php

use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('pristup sa validnim x-api-key radi', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/checkins', [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200);
});

it('pristup sa nevalidnim x-api-key vraća 401', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->getJson('/api/v1/checkins', [
        'x-api-key' => 'INVALID_KEY',
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_API_KEY');
});
