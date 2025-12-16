<?php

namespace Tests\Feature\Admin;

use App\Models\SmtpProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmtpProviderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_smtp_index()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'org_name' => 'Test Org',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.smtp.index'));

        $response->assertStatus(200);
    }

    public function test_superadmin_can_create_global_smtp_provider()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'org_name' => 'Test Org',
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.smtp.store'), [
            'name' => 'Global SMTP',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'password',
            'encryption' => 'tls',
            'from_address' => 'noreply@example.com',
            'from_name' => 'System',
            'is_global' => true,
        ]);

        $response->assertRedirect(route('admin.smtp.index'));
        $this->assertDatabaseHas('smtp_providers', [
            'name' => 'Global SMTP',
            'is_global' => true,
        ]);
    }

    public function test_superadmin_can_update_global_smtp_provider()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'org_name' => 'Test Org',
        ]);

        $smtp = SmtpProvider::create([
            'user_id' => $superadmin->id,
            'name' => 'Old Name',
            'host' => 'smtp.old.com',
            'port' => 587,
            'username' => 'olduser',
            'password' => 'oldpass',
            'encryption' => 'tls',
            'from_address' => 'old@example.com',
            'from_name' => 'Old System',
            'is_global' => true,
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.smtp.update', $smtp), [
            'name' => 'New Name',
            'host' => 'smtp.new.com',
            'port' => 465,
            'username' => 'newuser',
            'password' => 'newpass',
            'encryption' => 'ssl',
            'from_address' => 'new@example.com',
            'from_name' => 'New System',
            'is_global' => true,
        ]);

        $response->assertRedirect(route('admin.smtp.index'));
        $this->assertDatabaseHas('smtp_providers', [
            'id' => $smtp->id,
            'name' => 'New Name',
            'host' => 'smtp.new.com',
        ]);
    }
}
