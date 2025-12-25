<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the database to ensure roles and permissions exist
        $this->seed();

        // Retrieve the superadmin user created by DatabaseSeeder
        $this->user = User::where('email', 'admin@example.com')->first();

        if (! $this->user) {
            $this->fail('Superadmin user not found. Ensure DatabaseSeeder creates a user with email admin@example.com');
        }
    }

    /**
     * Test that critical admin and leader pages are accessible by the superuser.
     */
    public function test_superuser_can_access_critical_links(): void
    {
        $urls = [
            // Admin Routes
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.templates.certificates.index'),
            route('admin.templates.email.index'),
            route('admin.logs.index'),
            route('admin.documentation.index'),
            route('admin.oidc.edit'),

            // Leader Routes (Superadmin should also have access or be able to access these)
            route('dashboard.certificates.index'),
            route('dashboard.templates.certificates.index'),
            route('dashboard.templates.email.index'),
            route('dashboard.smtp.index'),
            route('dashboard.configuration.index'),
            route('dashboard.documentation.index'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->user)->get($url);

            // Debug: Dump content on failure to see the actual error in CI logs
            if ($response->status() !== 200) {
                echo "\nFailed URL: ".$url."\n";
                echo 'Status Code: '.$response->status()."\n";
                echo "Response Content:\n".substr($response->getContent(), 0, 2000)."\n"; // Limit output
            }

            // Check for 200 OK.
            // Note: Some pages might redirect if not properly set up, but for a superadmin
            // on index pages, we generally expect 200.
            // If some redirect (e.g. to a setup page), 302 might be acceptable,
            // but for "accessibility" 200 is the gold standard for index pages.
            $response->assertStatus(200);
        }
    }
}
