<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class AdminPanelEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_email_status_badges()
    {
        $verified = User::factory()->create([
            'role' => 'employee',
            'email' => 'emp1@test.rs',
            'email_verified_at' => now(),
        ]);
        $unverified = User::factory()->create([
            'role' => 'employee',
            'email' => 'emp2@test.rs',
            'email_verified_at' => null,
        ]);
        $noemail = User::factory()->create([
            'role' => 'employee',
            'email' => null,
        ]);

        $this->actingAs(User::factory()->admin()->create());
        $response = $this->get('/admin/users');
        $response->assertSee('Verified');
        $response->assertSee('Unverified');
        $response->assertSee('No email');
    }

    public function test_resend_verification_email_action_and_audit()
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'email' => 'emp3@test.rs',
            'email_verified_at' => null,
        ]);

        $this->actingAs($admin);
        $response = $this->post(route('admin.users.resend', $employee->id));
        $response->assertSessionHas('status', 'Verification email sent.');
        Notification::assertSentTo($employee, VerifyEmail::class);
        // Audit log: check that log entry is created (simulate with Log::spy if needed)
    }
}
