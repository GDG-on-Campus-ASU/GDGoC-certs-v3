<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_superadmin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_admin_dashboard(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_superadmin_can_view_user_list(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        User::factory()->create([
            'role' => 'leader',
            'name' => 'Test Leader',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Leader');
    }

    public function test_superadmin_can_create_new_user(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'New Leader',
            'email' => 'newleader@example.com',
            'password' => 'password123',
            'role' => 'leader',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newleader@example.com',
            'role' => 'leader',
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_update_user(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.users.update', $leader), [
            'name' => 'Updated Name',
            'org_name' => 'Test Org',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $leader->id,
            'name' => 'Updated Name',
            'org_name' => 'Test Org',
        ]);
    }

    public function test_superadmin_can_terminate_user_with_reason(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.users.update', $leader), [
            'name' => $leader->name,
            'org_name' => $leader->org_name,
            'status' => 'terminated',
            'termination_reason' => 'Policy violation',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $leader->id,
            'status' => 'terminated',
            'termination_reason' => 'Policy violation',
        ]);
    }

    public function test_superadmin_cannot_edit_other_superadmins(): void
    {
        $superadmin1 = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $superadmin2 = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin1)->get(route('admin.users.edit', $superadmin2));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_delete_leader(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.users.destroy', $leader));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $leader->id,
        ]);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_superadmin_can_create_admin_user(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_leaders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'name' => 'Test Leader',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Leader');
    }

    public function test_admin_cannot_see_other_admins_in_list(): void
    {
        $admin1 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin2 = User::factory()->create([
            'role' => 'admin',
            'name' => 'Other Admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin1)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Other Admin');
    }

    public function test_admin_can_create_leader(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Leader',
            'email' => 'newleader@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'newleader@example.com',
            'role' => 'leader',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_edit_leader(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $leader), [
            'name' => 'Updated Leader Name',
            'org_name' => 'Test Org',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $leader->id,
            'name' => 'Updated Leader Name',
            'org_name' => 'Test Org',
        ]);
    }

    public function test_admin_cannot_edit_other_admin(): void
    {
        $admin1 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin1)->get(route('admin.users.edit', $admin2));

        $response->assertStatus(403);
    }

    public function test_admin_cannot_update_other_admin(): void
    {
        $admin1 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin1)->put(route('admin.users.update', $admin2), [
            'name' => 'Updated Name',
            'org_name' => 'Test Org',
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_delete_other_admin(): void
    {
        $admin1 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin2 = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin1)->delete(route('admin.users.destroy', $admin2));

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_leader(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $leader = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $leader));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $leader->id,
        ]);
    }

    public function test_superadmin_can_see_admins_in_list(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Test Admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Admin');
    }

    public function test_superadmin_can_edit_admin(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.users.update', $admin), [
            'name' => 'Updated Admin Name',
            'org_name' => 'Test Org',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Updated Admin Name',
            'org_name' => 'Test Org',
        ]);
    }

    public function test_superadmin_can_delete_admin(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_access_superadmin_only_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Test OIDC settings (superadmin only)
        $response = $this->actingAs($admin)->get(route('admin.oidc.edit'));
        $response->assertStatus(403);

        // Test login logs (superadmin only)
        $response = $this->actingAs($admin)->get(route('admin.logs.index'));
        $response->assertStatus(403);
    }
}
