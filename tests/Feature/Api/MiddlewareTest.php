<?php

use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Support\Facades\Hash;

it('blokira zahtev bez validnog x-api-key', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $token = auth('api')->login($user);

    $response = $this->postJson('/api/v1/checkin', [
        'facility_id' => 1,
        'user_id' => $user->id,
        'method' => 'qr',
        'distance_m' => 0,
    ], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(401)
    ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('blokira zahtev za suspendovanu kompaniju', function () {
    $company = Company::factory()->create(['status' => 'suspended']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($user);

    $response = $this->postJson('/api/v1/checkin', [
        'facility_id' => 1,
        'user_id' => $user->id,
        'method' => 'qr',
        'distance_m' => 0,
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'SUSPENDED');
});

it('propusta zahtev za aktivnu kompaniju sa validnim x-api-key', function () {
    $company = Company::factory()->create(['status' => 'active']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
    $token = auth('api')->login($user);

    $facility = Facility::factory()->create(['company_id' => $company->id]);
    $response = $this->postJson('/api/v1/checkin', [
        'facility_id' => $facility->id,
        'user_id' => $user->id,
        'method' => 'qr',
        'distance_m' => 0,
    ], [
        'x-api-key' => $apiKey->key,
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message', 'checkin_id']);
});
