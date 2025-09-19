<?php
namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Company;
use App\Models\Facility;
use App\Models\Location;
use App\Models\AttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_creates_attendance_session_and_returns_id()
    {
        $this->withoutMiddleware(\App\Http\Middleware\JsonThrottle::class);
        $company = Company::factory()->create([
            'anti_spam_min_interval' => 0,
            'min_inout_gap_min' => 0,
        ]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'company_admin', 'password' => bcrypt('tajna123')]);
        $facility = Facility::factory()->create([
            'company_id' => $company->id,
        ]);
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'facility_id' => $facility->id,
        ]);
        $apiKey = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
        $token = auth('api')->login($user);
        $this->actingAs($user);
        $payload = [
            'company_id' => $company->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
            'method' => '',
            'action' => 'in',
        ];
        $headers = [
            'x-api-key' => $apiKey->key,
            'Authorization' => 'Bearer ' . $token,
        ];
        $response = $this->postJson('/api/v1/checkin', $payload, $headers);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'checkin_id', 'session_id', 'attendance_session' => ['id', 'in_at', 'out_at', 'duration_sec', 'status', 'under_threshold']
        ]);
        $sessionId = $response->json('session_id');
        $session = AttendanceSession::find($sessionId);
        $this->assertNotNull($session);
        $this->assertEquals('open', $session->status);
    }

    public function test_out_closes_attendance_session_and_sets_duration()
    {
        $this->withoutMiddleware(\App\Http\Middleware\JsonThrottle::class);
        $company = Company::factory()->create([
            'anti_spam_min_interval' => 0,
            'min_inout_gap_min' => 0,
        ]);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'company_admin', 'password' => bcrypt('tajna123')]);
        $facility = Facility::factory()->create([
            'company_id' => $company->id,
        ]);
        $location = Location::factory()->create([
            'company_id' => $company->id,
            'facility_id' => $facility->id,
        ]);
        $apiKey = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id, 'active' => true]);
        $token = auth('api')->login($user);
        $this->actingAs($user);
        // IN
        $inPayload = [
            'company_id' => $company->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
            'method' => '',
            'action' => 'in',
        ];
        $headers = [
            'x-api-key' => $apiKey->key,
            'Authorization' => 'Bearer ' . $token,
        ];
    $inResponse = $this->postJson('/api/v1/checkin', $inPayload, $headers);
    // Debug: log IN response and timestamps
    fwrite(STDERR, "IN response: " . json_encode($inResponse->json()) . "\n");
    $sessionId = $inResponse->json('session_id');
    $inSession = \App\Models\AttendanceSession::find($sessionId);
    fwrite(STDERR, "IN at: " . ($inSession ? $inSession->in_at : 'null') . "\n");
    sleep(1); // Add 1 second delay between IN and OUT
        // OUT
        $outPayload = [
            'company_id' => $company->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
            'method' => '',
            'action' => 'out',
            'session_id' => $sessionId,
        ];
        $outResponse = $this->postJson('/api/v1/checkin', $outPayload, $headers);
        // Debug: log OUT response and timestamps
        fwrite(STDERR, "OUT response: " . json_encode($outResponse->json()) . "\n");
        $outSession = \App\Models\AttendanceSession::find($sessionId);
        fwrite(STDERR, "OUT at: " . ($outSession ? $outSession->out_at : 'null') . "\n");
        // Debug: log company policy values
        $companyDebug = \App\Models\Company::find($company->id);
        fwrite(STDERR, "Company anti_spam_min_interval: " . ($companyDebug ? $companyDebug->anti_spam_min_interval : 'null') . "\n");
        fwrite(STDERR, "Company min_inout_gap_min: " . ($companyDebug ? $companyDebug->min_inout_gap_min : 'null') . "\n");
        $outResponse->assertStatus(200);
        $outResponse->assertJsonStructure([
            'checkin_id', 'session_id', 'attendance_session' => ['id', 'in_at', 'out_at', 'duration_sec', 'status', 'under_threshold']
        ]);
        $session = AttendanceSession::find($sessionId);
        $this->assertEquals('closed', $session->status);
        $this->assertNotNull($session->duration_sec);
    }
}
