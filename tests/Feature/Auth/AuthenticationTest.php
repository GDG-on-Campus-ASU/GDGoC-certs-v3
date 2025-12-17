<?php

namespace Tests\Feature\Auth;

use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_sso_login_button_not_shown_when_oidc_not_configured(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('SSO Login');
    }

    public function test_sso_login_button_shown_when_oidc_configured(): void
    {
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
            'login_endpoint_url' => 'https://example.com/auth',
            'token_endpoint_url' => 'https://example.com/token',
            'userinfo_endpoint_url' => 'https://example.com/userinfo',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SSO Login');
    }

    public function test_sso_login_button_not_shown_when_oidc_partially_configured(): void
    {
        // Create OIDC settings without login_endpoint_url
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('SSO Login');
    }

    public function test_session_lifetime_is_configured_to_8_hours(): void
    {
        // Verify session lifetime is set to 480 minutes (8 hours)
        $sessionLifetime = config('session.lifetime');
        
        $this->assertEquals(480, $sessionLifetime, 'Session lifetime should be 480 minutes (8 hours)');
    }
}
