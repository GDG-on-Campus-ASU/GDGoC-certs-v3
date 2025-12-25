<?php

namespace Tests\Feature\Security;

use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Support\Str;

class OidcAccountLinkingTest extends TestCase
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

    public function test_callback_does_not_link_existing_user_if_email_not_verified()
    {
        // Create existing user
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'oauth_provider' => null,
            'oauth_id' => null,
        ]);

        // Mock OIDC provider responses with email_verified = false
        Http::fake([
            'https://oidc.example.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'id_token' => 'test-id-token',
            ], 200),
            'https://oidc.example.com/userinfo' => Http::response([
                'sub' => 'attacker-oidc-id',
                'name' => 'Attacker',
                'email' => 'victim@example.com',
                'email_verified' => false,
            ], 200),
        ]);

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $response = $this->get(route('oauth.callback.fallback', [
            'code' => 'auth-code-123',
            'state' => $state,
        ]));

        // Assert: User is NOT linked.
        $user->refresh();
        $this->assertNull($user->oauth_provider, 'User should not have been linked to OIDC provider');
        $this->assertNull($user->oauth_id, 'User oauth_id should be null');

        // Assert response status (should be a redirect to login with error, or similar)
        $response->assertStatus(302);

        // Assert we get the specific error message instead of a generic exception error
        $response->assertSessionHas('error', 'Email account exists but OIDC email is not verified.');
    }

    public function test_callback_links_existing_user_if_email_verified()
    {
        // Create existing user
        $user = User::factory()->create([
            'email' => 'victim@example.com',
            'oauth_provider' => null,
            'oauth_id' => null,
        ]);

        // Mock OIDC provider responses with email_verified = true
        Http::fake([
            'https://oidc.example.com/token' => Http::response([
                'access_token' => 'test-access-token',
                'id_token' => 'test-id-token',
            ], 200),
            'https://oidc.example.com/userinfo' => Http::response([
                'sub' => 'legit-oidc-id',
                'name' => 'Legit User',
                'email' => 'victim@example.com',
                'email_verified' => true,
            ], 200),
        ]);

        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $response = $this->get(route('oauth.callback.fallback', [
            'code' => 'auth-code-123',
            'state' => $state,
        ]));

        // Assert: User IS linked.
        $user->refresh();
        $this->assertEquals('oidc', $user->oauth_provider);
        $this->assertEquals('legit-oidc-id', $user->oauth_id);
    }
}
