<?php

namespace Tests\Feature\Admin;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_creates_log_entry(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('login_logs', [
            'email' => 'test@example.com',
            'success' => true,
        ]);
    }

    public function test_failed_login_creates_log_entry(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertDatabaseHas('login_logs', [
            'email' => 'test@example.com',
            'success' => false,
        ]);
    }

    public function test_superadmin_can_view_login_logs(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        LoginLog::create([
            'email' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'success' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('test@example.com');
    }

    public function test_superadmin_can_access_rss_feed(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        LoginLog::create([
            'email' => 'test@example.com',
            'ip_address' => '127.0.0.1',
            'success' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.logs.feed'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/rss+xml');
        $response->assertSee('test@example.com');
    }

    public function test_non_superadmin_cannot_view_login_logs(): void
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.logs.index'));

        $response->assertStatus(403);
    }
}
