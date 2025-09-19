<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Company;
use App\Models\AttendanceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportAttendanceSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_attendance_sessions_csv(): void
    {
        $company  = Company::factory()->create(['status' => 'active']);
        $apiKey   = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id]);
        $admin    = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $facility = \App\Models\Facility::factory()->create(['company_id' => $company->id]);
        $location = \App\Models\Location::factory()->create([
            'company_id'  => $company->id,
            'facility_id' => $facility->id,
        ]);

        AttendanceSession::factory()->count(3)->create([
            'company_id'  => $company->id,
            'user_id'     => $admin->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
        ]);

        // Dijagnostika
        $diag = [
            'company_id'           => $company->id,
            'admin_id'             => $admin->id,
            'session_user_ids'     => AttendanceSession::pluck('user_id')->toArray(),
            'session_company_ids'  => AttendanceSession::pluck('company_id')->toArray(),
            'sessions'             => AttendanceSession::all()->toArray(),
        ];
        fwrite(STDERR, print_r($diag, true));

        $this->assertDatabaseCount('attendance_sessions', 3);

        $this->withoutMiddleware();
        $this->actingAs($admin);

        $response = $this->get('/api/v1/exports/attendance-sessions?format=csv', [
            'x-api-key' => $apiKey->key,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    fwrite(STDERR, $csv);
    $this->assertStringContainsString('session_id', $csv);
    $this->assertStringContainsString((string) $company->id, $csv);
    }

    public function test_employee_can_export_only_own_sessions(): void
    {
        $company  = Company::factory()->create(['status' => 'active']);
        $apiKey   = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['company_id' => $company->id, 'role' => 'employee']);
        $facility = \App\Models\Facility::factory()->create(['company_id' => $company->id]);
        $location = \App\Models\Location::factory()->create([
            'company_id'  => $company->id,
            'facility_id' => $facility->id,
        ]);

        AttendanceSession::factory()->count(2)->create([
            'company_id'  => $company->id,
            'user_id'     => $employee->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
        ]);

        AttendanceSession::factory()->count(2)->create([
            'company_id'  => $company->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
        ]);

        // Dijagnostika
        $diag = [
            'company_id'           => $company->id,
            'employee_id'          => $employee->id,
            'session_user_ids'     => AttendanceSession::pluck('user_id')->toArray(),
            'session_company_ids'  => AttendanceSession::pluck('company_id')->toArray(),
            'sessions'             => AttendanceSession::all()->toArray(),
        ];
        fwrite(STDERR, print_r($diag, true));

        $this->assertDatabaseCount('attendance_sessions', 4);

        $this->withoutMiddleware();
        $this->actingAs($employee);

        $response = $this->get('/api/v1/exports/attendance-sessions?format=csv', [
            'x-api-key' => $apiKey->key,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    fwrite(STDERR, $csv);
    $this->assertStringContainsString((string) $employee->id, $csv);
    }

    public function test_filter_by_date_and_status(): void
    {
        $company  = Company::factory()->create(['status' => 'active']);
        $apiKey   = \App\Models\CompanyApiKey::factory()->create(['company_id' => $company->id]);
        $admin    = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $facility = \App\Models\Facility::factory()->create(['company_id' => $company->id]);
        $location = \App\Models\Location::factory()->create([
            'company_id'  => $company->id,
            'facility_id' => $facility->id,
        ]);

        AttendanceSession::factory()->create([
            'company_id'  => $company->id,
            'user_id'     => $admin->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
            'in_at'       => now()->subDays(2),
            'status'      => 'open',
        ]);

        AttendanceSession::factory()->create([
            'company_id'  => $company->id,
            'user_id'     => $admin->id,
            'facility_id' => $facility->id,
            'location_id' => $location->id,
            'in_at'       => now(),
            'status'      => 'closed',
        ]);

        // Dijagnostika
        $diag = [
            'company_id'           => $company->id,
            'admin_id'             => $admin->id,
            'session_user_ids'     => AttendanceSession::pluck('user_id')->toArray(),
            'session_company_ids'  => AttendanceSession::pluck('company_id')->toArray(),
            'sessions'             => AttendanceSession::all()->toArray(),
        ];
        fwrite(STDERR, print_r($diag, true));

        $this->assertDatabaseCount('attendance_sessions', 2);

        $this->withoutMiddleware();
        $this->actingAs($admin);

        $response = $this->get('/api/v1/exports/attendance-sessions?format=csv&status=closed&from=' . now()->subDay()->toDateString(), [
            'x-api-key' => $apiKey->key,
        ]);

        $response->assertStatus(200);
    $csv = $response->streamedContent();
    fwrite(STDERR, $csv);
    $this->assertStringContainsString('closed', $csv);
    $this->assertStringNotContainsString('open', $csv);
    }
}
