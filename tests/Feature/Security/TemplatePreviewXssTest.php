<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatePreviewXssTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_template_preview_iframe_is_sandboxed()
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)
            ->get(route('admin.templates.email.create'));

        $response->assertStatus(200);
        // Assert the iframe exists
        $response->assertSee('<iframe id="preview-body-frame"', false);
        // Assert the sandbox attribute is present
        $response->assertSee('sandbox="allow-same-origin"', false);
    }

    public function test_certificate_template_preview_iframe_is_sandboxed()
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)
            ->get(route('admin.templates.certificates.create'));

        $response->assertStatus(200);

        // In the fixed version, this should be an iframe with sandbox
        // We expect this to fail initially as it is currently a div
        $response->assertSee('sandbox="allow-same-origin"', false);
    }
}
