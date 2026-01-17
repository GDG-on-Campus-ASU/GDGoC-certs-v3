<?php

namespace Tests\Feature\Admin;

use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TemplateSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_template_index_selects_specific_columns(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        CertificateTemplate::factory()->count(3)->create([
            'content' => str_repeat('A', 1000), // Simulate large content
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.templates.certificates.index'));

        $response->assertOk();

        $log = DB::getQueryLog();
        $templateQuery = collect($log)->first(function ($query) {
            return str_contains($query['query'], 'select')
                && str_contains($query['query'], 'certificate_templates')
                && !str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($templateQuery, 'Query for certificate templates not found.');

        // Assert that we are selecting specific columns.
        $this->assertStringContainsString('select', $templateQuery['query']);
        $this->assertStringContainsString('id', $templateQuery['query']);
        $this->assertStringContainsString('user_id', $templateQuery['query']);
        $this->assertStringContainsString('name', $templateQuery['query']);
        $this->assertStringContainsString('type', $templateQuery['query']);
        $this->assertStringContainsString('is_global', $templateQuery['query']);
        $this->assertStringContainsString('created_at', $templateQuery['query']);

        $this->assertStringNotContainsString('content', $templateQuery['query']);
    }

    public function test_email_template_index_selects_specific_columns(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        EmailTemplate::factory()->count(3)->create([
            'body' => str_repeat('A', 1000), // Simulate large content
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.templates.email.index'));

        $response->assertOk();

        $log = DB::getQueryLog();
        $templateQuery = collect($log)->first(function ($query) {
            return str_contains($query['query'], 'select')
                && str_contains($query['query'], 'email_templates')
                && !str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($templateQuery, 'Query for email templates not found.');

        $this->assertStringContainsString('select', $templateQuery['query']);
        $this->assertStringContainsString('id', $templateQuery['query']);
        $this->assertStringContainsString('user_id', $templateQuery['query']);
        $this->assertStringContainsString('name', $templateQuery['query']);
        $this->assertStringContainsString('subject', $templateQuery['query']);
        $this->assertStringContainsString('is_global', $templateQuery['query']);
        $this->assertStringContainsString('created_at', $templateQuery['query']);

        $this->assertStringNotContainsString('body', $templateQuery['query']);
    }
}
