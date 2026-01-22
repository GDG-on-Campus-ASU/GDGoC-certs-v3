<?php

namespace Tests\Feature\Admin;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_queries_are_optimized(): void
    {
        // Setup data
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create users for stats
        User::factory()->count(2)->create(['role' => 'leader', 'status' => 'active']);
        User::factory()->count(1)->create(['role' => 'leader', 'status' => 'suspended']);
        User::factory()->count(1)->create(['role' => 'leader', 'status' => 'terminated']);

        // Create logs for stats
        LoginLog::create(['email' => 'a@b.com', 'ip_address' => '1.1.1.1', 'success' => true]);
        LoginLog::create(['email' => 'a@b.com', 'ip_address' => '1.1.1.1', 'success' => true]);
        LoginLog::create(['email' => 'a@b.com', 'ip_address' => '1.1.1.1', 'success' => false]);

        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Before optimization, we expect at least 6 separate count queries + auth queries
        // 1. Total users
        // 2. Active users
        // 3. Suspended users
        // 4. Terminated users
        // 5. Recent logins
        // 6. Failed logins

        // Check that we are getting the correct data
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_users'] === 4
                && $stats['active_users'] === 2
                && $stats['suspended_users'] === 1
                && $stats['terminated_users'] === 1
                && $stats['recent_logins'] === 2
                && $stats['failed_logins'] === 1;
        });
    }
}
