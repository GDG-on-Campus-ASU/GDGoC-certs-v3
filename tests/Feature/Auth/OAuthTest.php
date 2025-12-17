<?php

namespace Tests\Feature\Auth;

use App\Models\OidcSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_button_is_not_shown_when_oidc_is_not_configured(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('SSO Login');
    }

    public function test_sso_button_is_shown_when_oidc_is_configured(): void
    {
        // Create a configured OIDC setting
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'login_endpoint_url' => 'https://example.com/oauth/authorize',
            'token_endpoint_url' => 'https://example.com/oauth/token',
            'userinfo_endpoint_url' => 'https://example.com/oauth/userinfo',
            'scope' => 'openid profile email',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('SSO Login');
    }

    public function test_oauth_redirect_fails_when_not_configured(): void
    {
        $response = $this->get('/auth/redirect');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'SSO authentication is not configured.');
    }

    public function test_oauth_redirect_works_when_configured(): void
    {
        // Create a configured OIDC setting
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'login_endpoint_url' => 'https://example.com/oauth/authorize',
            'token_endpoint_url' => 'https://example.com/oauth/token',
            'userinfo_endpoint_url' => 'https://example.com/oauth/userinfo',
            'scope' => 'openid profile email',
        ]);

        $response = $this->get('/auth/redirect');

        // Should redirect to the OIDC provider
        $response->assertRedirect();
        $this->assertStringContainsString('example.com/oauth/authorize', $response->headers->get('Location'));
    }

    public function test_oauth_callback_fails_when_not_configured(): void
    {
        $response = $this->get('/auth/callback?code=test-code&state=test-state');

        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'SSO authentication is not configured.');
    }

    public function test_sso_button_is_not_shown_when_oidc_is_partially_configured(): void
    {
        // Create an OIDC setting with missing required fields
        OidcSetting::create([
            'client_id' => 'test-client-id',
            // Missing client_secret, login_endpoint_url, and userinfo_endpoint_url
        ]);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('SSO Login');
    }
}
