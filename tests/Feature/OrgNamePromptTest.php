<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgNamePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_org_name_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_user_without_org_name_is_redirected_to_profile(): void
    {
        $user = User::factory()->withoutOrgName()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('info', 'Please complete your organization name to continue.');
    }

    public function test_user_without_org_name_can_access_profile_page(): void
    {
        $user = User::factory()->withoutOrgName()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
    }

    public function test_user_without_org_name_can_update_profile_with_org_name(): void
    {
        $user = User::factory()->withoutOrgName()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'org_name' => 'My New Organization',
            ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('My New Organization', $user->org_name);
    }

    public function test_profile_update_requires_org_name(): void
    {
        // Create user without org_name to test that org_name is required for first-time set
        $user = User::factory()->withoutOrgName()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                // org_name is missing
            ]);

        $response->assertSessionHasErrors(['org_name']);
    }

    public function test_user_can_access_dashboard_after_setting_org_name(): void
    {
        $user = User::factory()->withoutOrgName()->create();

        // First attempt - redirected to profile
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('profile.edit'));

        // Update profile with org_name
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'org_name' => 'Test Organization',
        ]);

        $user->refresh();

        // Second attempt - can access dashboard
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_user_without_org_name_can_logout(): void
    {
        $user = User::factory()->withoutOrgName()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
