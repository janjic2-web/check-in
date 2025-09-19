<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create([
            'status' => 'active',
            'default_radius_m' => 100,
        ]);
        $this->apiKey = CompanyApiKey::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $this->facility = Facility::factory()->create(['company_id' => $this->company->id]);
        $this->location = Location::factory()->create([
            'company_id' => $this->company->id,
            'facility_id' => $this->facility->id,
            'lat' => 44.7866,
            'lng' => 20.4489,
        ]);
        $this->jwt = auth('api')->login($this->user);
    }

    public function test_missing_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            // nedostaje facility_id, location_id
            'method' => 'qr',
        ]);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_invalid_location()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => 99999, // nepostojeća lokacija
            'method' => 'qr',
        ]);
        $response->assertStatus(404);
        $response->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_unauthenticated()
    {
        $response = $this->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'qr',
        ]);
        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
