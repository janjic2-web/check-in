<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Company;

it('user can check in to facility with valid API key', function () {
    $company = Company::factory()->create();
    $facility = Facility::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create(['company_id' => $company->id, 'password' => Hash::make('tajna123')]);
    $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);

    // Generiši JWT token za korisnika
    $token = auth('api')->login($user);

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

it('user cannot check in with invalid API key', function () {
    $company = Company::factory()->create();
    $facility = Facility::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create(['company_id' => $company->id, 'password' => Hash::make('tajna123')]);

    $response = $this->postJson('/api/v1/checkin', [
        'facility_id' => $facility->id,
        'user_id' => $user->id,
    ], [
        'x-api-key' => 'INVALID_KEY',
    ]);

    $response->assertStatus(401)
        ->assertJsonStructure(['error']);
});
