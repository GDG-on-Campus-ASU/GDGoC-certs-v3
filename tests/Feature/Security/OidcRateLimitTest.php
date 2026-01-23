<?php

namespace Tests\Feature\Security;

use App\Models\OidcSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OidcRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup OIDC settings so the controller logic (if reached) works enough to redirect
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'login_endpoint_url' => 'https://oidc.example.com/auth',
            'token_endpoint_url' => 'https://oidc.example.com/token',
            'userinfo_endpoint_url' => 'https://oidc.example.com/userinfo',
            'token_validation_endpoint_url' => 'https://oidc.example.com/introspect',
            'link_existing_users' => true,
            'create_new_users' => true,
        ]);
    }

    public function test_oidc_redirect_route_is_rate_limited()
    {
        // Hit the endpoint 10 times - should succeed (302 Redirect to OIDC provider)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get(route('oauth.redirect.fallback'));
            $response->assertStatus(302);
        }

        // 11th time should fail with 429 Too Many Requests
        $response = $this->get(route('oauth.redirect.fallback'));
        $response->assertStatus(429);
    }

    public function test_oidc_callback_route_is_rate_limited()
    {
        // Hit the endpoint 10 times
        for ($i = 0; $i < 10; $i++) {
            // Even with missing params, it redirects (302) to login with error
            $response = $this->get(route('oauth.callback.fallback'));
            $response->assertStatus(302);
        }

        // 11th time should fail with 429 Too Many Requests
        $response = $this->get(route('oauth.callback.fallback'));
        $response->assertStatus(429);
    }
}
