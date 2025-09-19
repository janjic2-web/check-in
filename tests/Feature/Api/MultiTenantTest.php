<?php

use App\Models\Company;
use App\Models\CompanyApiKey;
use App\Models\User;
use App\Models\Facility;
use Illuminate\Support\Facades\Hash;

it('korisnik iz kompanije A ne vidi podatke kompanije B', function () {
    $companyA = Company::factory()->create(['status' => 'active']);
    $companyB = Company::factory()->create(['status' => 'active']);
    $facilityA = Facility::factory()->create(['company_id' => $companyA->id]);
    $facilityB = Facility::factory()->create(['company_id' => $companyB->id]);
    $userA = User::factory()->create([
        'company_id' => $companyA->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $userB = User::factory()->create([
        'company_id' => $companyB->id,
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('tajna123'),
    ]);
    $apiKeyA = CompanyApiKey::factory()->create(['company_id' => $companyA->id, 'active' => true]);
    $apiKeyB = CompanyApiKey::factory()->create(['company_id' => $companyB->id, 'active' => true]);
    $tokenA = auth('api')->login($userA);
    $tokenB = auth('api')->login($userB);

    // User A vidi samo facility A
    $responseA = $this->getJson('/api/v1/facilities/' . $facilityA->id, [
        'x-api-key' => $apiKeyA->key,
        'Authorization' => 'Bearer ' . $tokenA,
    ]);
    $responseA->assertStatus(200)
        ->assertJsonFragment(['id' => $facilityA->id]);

    // User A ne vidi facility B
    $responseA2 = $this->getJson('/api/v1/facilities/' . $facilityB->id, [
        'x-api-key' => $apiKeyA->key,
        'Authorization' => 'Bearer ' . $tokenA,
    ]);
    $responseA2->assertStatus(404);

    // User B vidi samo facility B
    $responseB = $this->getJson('/api/v1/facilities/' . $facilityB->id, [
        'x-api-key' => $apiKeyB->key,
        'Authorization' => 'Bearer ' . $tokenB,
    ]);
    $responseB->assertStatus(200)
        ->assertJsonFragment(['id' => $facilityB->id]);

    // User B ne vidi facility A
    $responseB2 = $this->getJson('/api/v1/facilities/' . $facilityA->id, [
        'x-api-key' => $apiKeyB->key,
        'Authorization' => 'Bearer ' . $tokenB,
    ]);
    $responseB2->assertStatus(404);
});
