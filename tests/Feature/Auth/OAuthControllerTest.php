<?php

namespace Tests\Feature\Auth;

use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Support\Str;

class OAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup OIDC settings
        OidcSetting::create([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret', // Will be encrypted by model
            'login_endpoint_url' => 'https://oidc.example.com/auth',
            'token_endpoint_url' => 'https://oidc.example.com/token',
            'userinfo_endpoint_url' => 'https://oidc.example.com/userinfo',
            'token_validation_endpoint_url' => 'https://oidc.example.com/introspect',
            'link_existing_users' => true,
            'create_new_users' => true,
        ]);
    }

    public function test_redirect_generates_correct_url()
    {
        $response = $this->get(route('oauth.redirect.fallback'));

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString('https://oidc.example.com/auth', $redirectUrl);
        $this->assertStringContainsString('client_id=test-client-id', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);

        // Verify state in session
        $this->assertNotNull(session('oauth_state'));
    }

    public function test_callback_handles_token_exchange_and_creates_user()
    {
        // Mock OIDC provider responses
        Http::fake([
            'https://oidc.example.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'id_token' => 'test-id-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://oidc.example.com/userinfo' => Http::response([
                'sub' => 'oidc-user-123',
                'name' => 'Test OIDC User',
                'email' => 'oidc@example.com',
                'preferred_username' => 'oidcuser',
            ], 200),
        ]);

        // Simulate state in session
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $response = $this->get(route('oauth.callback.fallback', [
            'code' => 'auth-code-123',
            'state' => $state,
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'oidc@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('oidc', $user->oauth_provider);
        $this->assertEquals('oidc-user-123', $user->oauth_id);
    }

    public function test_callback_links_existing_user()
    {
        // Create existing user
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'oauth_provider' => null,
            'oauth_id' => null,
        ]);

        // Mock OIDC provider responses
        Http::fake([
            'https://oidc.example.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'id_token' => 'test-id-token',
            ], 200),
            'https://oidc.example.com/userinfo' => Http::response([
                'sub' => 'oidc-user-456',
                'name' => 'Existing User',
                'email' => 'existing@example.com',
            ], 200),
        ]);

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $response = $this->get(route('oauth.callback.fallback', [
            'code' => 'auth-code-456',
            'state' => $state,
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertEquals('oidc', $user->oauth_provider);
        $this->assertEquals('oidc-user-456', $user->oauth_id);
    }

    public function test_callback_handles_errors()
    {
         // Mock OIDC provider token failure
        Http::fake([
            'https://oidc.example.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $response = $this->get(route('oauth.callback.fallback', [
            'code' => 'bad-code',
            'state' => $state,
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }
}
