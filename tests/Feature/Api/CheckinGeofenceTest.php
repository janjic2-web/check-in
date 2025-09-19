<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinGeofenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create([
            'status' => 'active',
            'allow_outside' => false,
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

    public function test_checkin_on_geofence_border_is_inside()
    {
        // Na granici (distance == radius)
        $lat = 44.7866;
        $lng = 20.4498; // ~100m istočno
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'lat' => $lat,
            'lng' => $lng,
            'method' => 'qr',
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'inside');
    }

    public function test_checkin_outside_geofence_denied()
    {
        // Izvan granice (distance > radius)
        $lat = 44.7866;
        $lng = 20.4510; // >100m istočno
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'lat' => $lat,
            'lng' => $lng,
            'method' => 'qr',
        ]);
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'OUTSIDE_DENIED');
    }
}
