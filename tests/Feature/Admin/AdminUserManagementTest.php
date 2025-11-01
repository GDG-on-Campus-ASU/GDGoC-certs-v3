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
}
