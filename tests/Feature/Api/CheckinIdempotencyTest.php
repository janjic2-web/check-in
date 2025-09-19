<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create([
            'status' => 'active',
            'default_radius_m' => 100,
            'anti_spam_min_interval' => 2,
            'min_inout_gap_min' => 2,
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

    public function test_idempotent_checkin()
    {
        $clientEventId = 'test-event-123';
        // Prvi checkin sa client_event_id
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'qr',
            'client_event_id' => $clientEventId,
        ]);
        $response1->assertStatus(200);
        // Dupli checkin sa istim client_event_id treba da bude ignorisan (409)
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'qr',
            'client_event_id' => $clientEventId,
        ]);
    $response2->assertStatus(429);
    $response2->assertJsonPath('error.code', 'RATE_LIMITED');
    }
}
