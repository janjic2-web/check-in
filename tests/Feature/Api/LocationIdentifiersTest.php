<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\Facility;
use App\Models\Location;
use App\Models\User;
use App\Models\LocationNfcTag;
use App\Models\LocationBleBeacon;
use App\Models\LocationQrCode;
use Tymon\JWTAuth\Facades\JWTAuth;

class LocationIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_edit_delete_nfc_tag()
    {
        $company = Company::factory()->create();
        $facility = Facility::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id, 'facility_id' => $facility->id]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $apiKey = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
        $token = JWTAuth::fromUser($admin);
        $headers = [
            'x-api-key' => $apiKey->key,
            'Authorization' => 'Bearer ' . $token,
        ];

        // Add NFC tag
        $response = $this->postJson("/api/v1/locations/{$location->id}/nfc-tags", [
            'tag_uid' => 'NFC123',
            'description' => 'Test NFC tag',
        ], $headers);
        $response->assertStatus(201);
        $tagId = $response->json('data.id');

        // Edit NFC tag
        $response = $this->patchJson("/api/v1/locations/{$location->id}/nfc-tags/{$tagId}", [
            'description' => 'Updated NFC tag',
        ], $headers);
        $response->assertStatus(200);
        $this->assertEquals('Updated NFC tag', $response->json('data.description'));

        // Delete NFC tag
        $response = $this->deleteJson("/api/v1/locations/{$location->id}/nfc-tags/{$tagId}", [], $headers);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('location_nfc_tags', ['id' => $tagId]);
    }

    public function test_admin_can_add_edit_delete_ble_beacon()
    {
        $company = Company::factory()->create();
        $facility = Facility::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id, 'facility_id' => $facility->id]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $apiKey = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
        $token = JWTAuth::fromUser($admin);
        $headers = [
            'x-api-key' => $apiKey->key,
            'Authorization' => 'Bearer ' . $token,
        ];

        // Add BLE beacon
        $response = $this->postJson("/api/v1/locations/{$location->id}/ble-beacons", [
            'beacon_id' => 'BLE123',
            'description' => 'Test BLE beacon',
        ], $headers);
        $response->assertStatus(201);
        $beaconId = $response->json('data.id');

        // Edit BLE beacon
        $response = $this->patchJson("/api/v1/locations/{$location->id}/ble-beacons/{$beaconId}", [
            'description' => 'Updated BLE beacon',
        ], $headers);
        $response->assertStatus(200);
        $this->assertEquals('Updated BLE beacon', $response->json('data.description'));

        // Delete BLE beacon
        $response = $this->deleteJson("/api/v1/locations/{$location->id}/ble-beacons/{$beaconId}", [], $headers);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('location_ble_beacons', ['id' => $beaconId]);
    }

    public function test_admin_can_add_edit_delete_qr_code()
    {
        $company = Company::factory()->create();
        $facility = Facility::factory()->create(['company_id' => $company->id]);
        $location = Location::factory()->create(['company_id' => $company->id, 'facility_id' => $facility->id]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $apiKey = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
        $token = JWTAuth::fromUser($admin);
        $headers = [
            'x-api-key' => $apiKey->key,
            'Authorization' => 'Bearer ' . $token,
        ];

        // Add QR code
        $response = $this->postJson("/api/v1/locations/{$location->id}/qr-codes", [
            'qr_payload' => 'QR123',
            'description' => 'Test QR code',
        ], $headers);
        $response->assertStatus(201);
        $qrId = $response->json('data.id');

        // Edit QR code
        $response = $this->patchJson("/api/v1/locations/{$location->id}/qr-codes/{$qrId}", [
            'description' => 'Updated QR code',
        ], $headers);
        $response->assertStatus(200);
        $this->assertEquals('Updated QR code', $response->json('data.description'));

        // Delete QR code
        $response = $this->deleteJson("/api/v1/locations/{$location->id}/qr-codes/{$qrId}", [], $headers);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('location_qr_codes', ['id' => $qrId]);
    }
}
