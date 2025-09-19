<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $apiKey;
    protected $user;
    protected $facility;
    protected $location;
    protected $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create([
            'status' => 'active',
            'anti_spam_min_interval' => 0,
            'min_inout_gap_min' => 0,
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

    public function test_checkin_rate_limiting()
    {
        // Prvi checkin prolazi
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'qr',
            'client_event_id' => 'event-0',
        ]);
        $response1->assertStatus(200);
        // Višestruki checkin u kratkom vremenu treba da bude blokiran (rate limit)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->jwt,
                'x-api-key' => $this->apiKey->key,
            ])->json('POST', '/api/v1/checkin', [
                'facility_id' => $this->facility->id,
                'location_id' => $this->location->id,
                'method' => 'qr',
                'client_event_id' => 'event-' . $i,
            ]);
        }
        $response->assertStatus(429);
        $response->assertJsonPath('error.code', 'RATE_LIMITED');
    }
}
