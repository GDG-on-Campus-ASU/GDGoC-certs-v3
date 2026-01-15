<?php

namespace Tests\Feature\Admin;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_correct_stats(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        // Create Users
        User::factory()->count(3)->create(['role' => 'leader', 'status' => 'active']);
        User::factory()->count(2)->create(['role' => 'leader', 'status' => 'suspended']);
        User::factory()->count(1)->create(['role' => 'leader', 'status' => 'terminated']);
        // Create an admin user (should not be counted in leader stats based on controller logic)
        User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Create Login Logs manually since no factory exists
        for ($i = 0; $i < 5; $i++) {
            LoginLog::create([
                'email' => 'user'.$i.'@example.com',
                'ip_address' => '127.0.0.1',
                'success' => true,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            LoginLog::create([
                'email' => 'fail'.$i.'@example.com',
                'ip_address' => '127.0.0.1',
                'success' => false,
            ]);
        }

        $response = $this->actingAs($superadmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Verify stats in the view data
        $stats = $response->viewData('stats');

        $this->assertEquals(6, $stats['total_users']); // 3 active + 2 suspended + 1 terminated = 6 leaders
        $this->assertEquals(3, $stats['active_users']);
        $this->assertEquals(2, $stats['suspended_users']);
        $this->assertEquals(1, $stats['terminated_users']);
        $this->assertEquals(5, $stats['recent_logins']);
        $this->assertEquals(2, $stats['failed_logins']);
    }
}
