<?php

namespace Tests\Feature\Admin;

use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OidcSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_oidc_settings(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.oidc.edit'));

        $response->assertStatus(200);
    }

    public function test_superadmin_can_update_oidc_settings(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->post(route('admin.oidc.update'), [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
            'scope' => 'openid profile email',
            'login_endpoint_url' => 'https://example.com/auth',
            'userinfo_endpoint_url' => 'https://example.com/userinfo',
            'token_validation_endpoint_url' => 'https://example.com/token',
            'end_session_endpoint_url' => 'https://example.com/logout',
            'identity_key' => 'sub',
            'link_existing_users' => false,
            'create_new_users' => false,
            'redirect_on_expiry' => false,
        ]);

        $response->assertRedirect(route('admin.oidc.edit'));
        $this->assertDatabaseHas('oidc_settings', [
            'client_id' => 'test-client-id',
            'scope' => 'openid profile email',
        ]);
    }

    public function test_client_secret_is_encrypted(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)->post(route('admin.oidc.update'), [
            'client_id' => 'test-client-id',
            'client_secret' => 'my-secret-password',
            'scope' => 'openid',
            'login_endpoint_url' => 'https://example.com/auth',
            'userinfo_endpoint_url' => 'https://example.com/userinfo',
            'token_validation_endpoint_url' => 'https://example.com/token',
            'end_session_endpoint_url' => 'https://example.com/logout',
            'identity_key' => 'sub',
        ]);

        $setting = OidcSetting::first();

        // The client_secret should be decrypted when accessed via the model
        $this->assertEquals('my-secret-password', $setting->client_secret);

        // But it should be encrypted in the database
        $this->assertNotEquals('my-secret-password', $setting->getRawOriginal('client_secret'));
    }

    public function test_non_superadmin_cannot_access_oidc_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.oidc.edit'));

        $response->assertStatus(403);
    }
}
