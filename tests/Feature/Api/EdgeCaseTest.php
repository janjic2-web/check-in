<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use Illuminate\Support\Str;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Kreiraj kompaniju, korisnika, facility, api key
        $this->company = Company::factory()->create(['status' => 'active']);
        $this->apiKey = CompanyApiKey::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'status' => 'active',
        ]);
        $this->facility = Facility::factory()->create(['company_id' => $this->company->id]);
        $this->jwt = auth('api')->login($this->user);
    }

    public function test_unauthorized_action_missing_api_key()
    {
        $response = $this->json('POST', '/api/v1/facilities', [
            'name' => 'Test Facility',
        ]);
        $response->assertStatus(401);
    }

    public function test_access_nonexistent_resource()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('GET', '/api/v1/facilities/999999');
        $response->assertStatus(404);
    }

    public function test_invalid_data_returns_validation_error()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/facilities', [
            'name' => '', // Prazno ime
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_wrong_method_returns_405()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('PUT', '/api/v1/facilities/' . $this->facility->id);
        $response->assertStatus(405);
    }
}
