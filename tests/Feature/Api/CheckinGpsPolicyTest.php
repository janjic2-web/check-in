<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;

class CheckinGpsPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Restore all middlewares to default
    }

    public function test_gps_required_returns_422()
    {
        $company = Company::factory()->create([
            'status' => 'active',
            'require_gps_checkin' => true,
        ]);
        $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $facility = Facility::factory()->create(['company_id' => $company->id]);
        $jwt = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwt,
            'x-api-key' => $apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $facility->id,
            // lat/lng nisu poslati
        ]);
        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'GPS_REQUIRED');
    }

    public function test_gps_not_required_returns_200()
    {
        $company = Company::factory()->create([
            'status' => 'active',
            'require_gps_checkin' => false,
        ]);
        $apiKey = CompanyApiKey::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $facility = Facility::factory()->create(['company_id' => $company->id]);
        $jwt = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $jwt,
            'x-api-key' => $apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $facility->id,
            // lat/lng nisu poslati
        ]);
        $response->assertStatus(200);
        $response->assertJsonMissing(['error']);
    }
}
