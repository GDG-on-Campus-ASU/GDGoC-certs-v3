<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        // Email verification should return 404 since it's disabled
        $response->assertStatus(404);
    }

    public function test_email_verification_route_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email/1/somehash');

        // Email verification should return 404 since it's disabled
        $response->assertStatus(404);
    }

    public function test_users_can_access_dashboard_without_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null, // Unverified email
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        // Users should be able to access dashboard without email verification
        $response->assertStatus(200);
    }
}
