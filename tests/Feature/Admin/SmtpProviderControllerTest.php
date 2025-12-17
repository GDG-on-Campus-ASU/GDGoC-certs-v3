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

    public function test_superadmin_can_delete_smtp_provider()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'org_name' => 'Test Org',
        ]);

        $smtp = SmtpProvider::create([
            'user_id' => $superadmin->id,
            'name' => 'Delete Me',
            'host' => 'smtp.delete.com',
            'port' => 587,
            'username' => 'deleteuser',
            'password' => 'deletepass',
            'encryption' => 'tls',
            'from_address' => 'delete@example.com',
            'from_name' => 'Delete System',
            'is_global' => true,
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.smtp.destroy', $smtp));

        $response->assertRedirect(route('admin.smtp.index'));
        $this->assertDatabaseMissing('smtp_providers', [
            'id' => $smtp->id,
        ]);
    }

    public function test_smtp_password_is_encrypted()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'org_name' => 'Test Org',
        ]);

        $password = 'secret-password-123';

        $this->actingAs($superadmin)->post(route('admin.smtp.store'), [
            'name' => 'Encrypted SMTP',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => $password,
            'encryption' => 'tls',
            'from_address' => 'noreply@example.com',
            'from_name' => 'System',
            'is_global' => true,
        ]);

        $smtp = SmtpProvider::where('name', 'Encrypted SMTP')->first();

        // Verify that the password attribute is decrypted
        $this->assertEquals($password, $smtp->password);

        // Verify that the password in the database is encrypted and not plain text
        $this->assertNotEquals($password, $smtp->getRawOriginal('password'));
        $this->assertNotEquals($smtp->password, $smtp->getRawOriginal('password'));
    }

    public function test_non_superadmin_cannot_access_smtp_index()
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'org_name' => 'Test Org',
        ]);

        $response = $this->actingAs($user)->get(route('admin.smtp.index'));

        $response->assertForbidden();
    }

    public function test_non_superadmin_cannot_create_smtp_provider()
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'org_name' => 'Test Org',
        ]);

        $response = $this->actingAs($user)->post(route('admin.smtp.store'), [
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

        $response->assertForbidden();
    }
}
