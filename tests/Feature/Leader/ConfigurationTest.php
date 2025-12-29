<?php

namespace Tests\Feature\Leader;

use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_access_configuration_page(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
            'role' => 'leader',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.configuration.index'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.configuration.index');
        $response->assertViewHas(['smtpProviders', 'userEmailTemplates', 'globalEmailTemplates', 'userCertificateTemplates', 'globalCertificateTemplates']);
    }

    public function test_configuration_page_loads_templates_correctly(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
            'role' => 'leader',
        ]);

        // Create user templates
        $userEmailTemplate = EmailTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'User Email Template',
            'body' => str_repeat('A', 10000), // Large body
        ]);

        $userCertTemplate = CertificateTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'User Cert Template',
            'content' => str_repeat('B', 10000), // Large content
        ]);

        // Create global templates
        $globalEmailTemplate = EmailTemplate::factory()->create([
            'user_id' => $user->id, // Global templates still have an owner usually, or null depending on implementation. Migration says foreignId user_id.
            'name' => 'Global Email Template',
            'is_global' => true,
            'body' => str_repeat('C', 10000),
        ]);

        $globalCertTemplate = CertificateTemplate::factory()->create([
            'user_id' => $user->id,
            'name' => 'Global Cert Template',
            'is_global' => true,
            'content' => str_repeat('D', 10000),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.configuration.index'));

        $response->assertStatus(200);

        // Check user email templates
        $userEmails = $response->viewData('userEmailTemplates');
        $this->assertTrue($userEmails->contains($userEmailTemplate));

        // Check global email templates
        $globalEmails = $response->viewData('globalEmailTemplates');
        $this->assertTrue($globalEmails->contains($globalEmailTemplate));

        // Check user cert templates
        $userCerts = $response->viewData('userCertificateTemplates');
        $this->assertTrue($userCerts->contains($userCertTemplate));

        // Check global cert templates
        $globalCerts = $response->viewData('globalCertificateTemplates');
        $this->assertTrue($globalCerts->contains($globalCertTemplate));
    }
}
