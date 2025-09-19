<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\CompanyApiKey;
use App\Models\Location;

class CheckinBleRssiTest extends TestCase
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

        // Kreiraj beacon za lokaciju
        $this->beacon = \App\Models\LocationBeacon::factory()->create([
            'company_id' => $this->company->id,
            'location_id' => $this->location->id,
            'uuid' => '123e4567-e89b-12d3-a456-426614174000',
            'major' => 1,
            'minor' => 1,
            'active' => true,
        ]);
    }

    public function test_ble_checkin_inside_rssi()
    {
        // Simuliraj BLE checkin sa jakim signalom (rssi -40)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'ble',
            'details' => [
                'uuid' => $this->beacon->uuid,
                'major' => $this->beacon->major,
                'minor' => $this->beacon->minor,
                'rssi' => -40,
            ],
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'inside');
    }

    public function test_ble_checkin_outside_rssi()
    {
        // Simuliraj BLE checkin sa slabim signalom (rssi -90)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwt,
            'x-api-key' => $this->apiKey->key,
        ])->json('POST', '/api/v1/checkin', [
            'facility_id' => $this->facility->id,
            'location_id' => $this->location->id,
            'method' => 'ble',
            'details' => [
                'uuid' => $this->beacon->uuid,
                'major' => $this->beacon->major,
                'minor' => $this->beacon->minor,
                'rssi' => -90,
            ],
        ]);
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'OUTSIDE_DENIED');
    }
}
