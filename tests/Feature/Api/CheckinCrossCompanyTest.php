<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinCrossCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected $companyA;
    protected $companyB;
    protected $apiKeyA;
    protected $apiKeyB;
    protected $userA;
    protected $userB;
    protected $facilityA;
    protected $facilityB;
    protected $locationA;
    protected $locationB;
    protected $jwtA;
    protected $jwtB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->companyA = Company::factory()->create(['status' => 'active']);
        $this->companyB = Company::factory()->create(['status' => 'active']);
        $this->apiKeyA = CompanyApiKey::factory()->create(['company_id' => $this->companyA->id]);
        $this->apiKeyB = CompanyApiKey::factory()->create(['company_id' => $this->companyB->id]);
        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'role' => 'employee',
            'status' => 'active',
        ]);
        $this->facilityA = Facility::factory()->create(['company_id' => $this->companyA->id]);
        $this->facilityB = Facility::factory()->create(['company_id' => $this->companyB->id]);
        $this->locationA = Location::factory()->create([
            'company_id' => $this->companyA->id,
            'facility_id' => $this->facilityA->id,
            'lat' => 44.7866,
            'lng' => 20.4489,
        ]);
        $this->locationB = Location::factory()->create([
            'company_id' => $this->companyB->id,
            'facility_id' => $this->facilityB->id,
            'lat' => 44.7866,
            'lng' => 20.4489,
        ]);
        $this->jwtA = auth('api')->login($this->userA);
        $this->jwtB = auth('api')->login($this->userB);
    }

    public function test_user_cannot_checkin_to_other_company_facility()
    {
        // User A pokušava checkin na facility B
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtA,
            'x-api-key' => $this->apiKeyA->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facilityB->id,
            'location_id' => $this->locationB->id,
            'method' => 'qr',
        ]);
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }
}
