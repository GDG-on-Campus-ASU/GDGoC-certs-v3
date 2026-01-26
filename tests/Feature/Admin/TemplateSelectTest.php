<?php

namespace Tests\Feature\Admin;

use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TemplateSelectTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_templates_index_optimizes_column_selection(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        // Create 3 templates with large content
        CertificateTemplate::factory()->count(3)->create([
            'content' => Str::repeat('Large Content ', 1000),
            'user_id' => $admin->id,
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.templates.certificates.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // Find the query for certificate_templates table
        $templateQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'select') &&
                   str_contains($query['query'], 'from "certificate_templates"') &&
                   ! str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($templateQuery, 'Certificate template query not found');

        // It should NOT select *
        $this->assertStringNotContainsString('select *', $templateQuery['query']);

        // It SHOULD select specific columns
        $this->assertStringContainsString('select "id"', $templateQuery['query']);
        $this->assertStringContainsString('"name"', $templateQuery['query']);

        // It should NOT select content
        $this->assertStringNotContainsString('"content"', $templateQuery['query']);
    }

    public function test_email_templates_index_optimizes_column_selection(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);

        // Create 3 templates with large body
        EmailTemplate::factory()->count(3)->create([
            'body' => Str::repeat('Large Body ', 1000),
            'user_id' => $admin->id,
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($admin)->get(route('admin.templates.email.index'));

        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // Find the query for email_templates table
        $templateQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'select') &&
                   str_contains($query['query'], 'from "email_templates"') &&
                   ! str_contains($query['query'], 'count(*)');
        });

        $this->assertNotNull($templateQuery, 'Email template query not found');

        // It should NOT select *
        $this->assertStringNotContainsString('select *', $templateQuery['query']);

        // It SHOULD select specific columns
        $this->assertStringContainsString('select "id"', $templateQuery['query']);
        $this->assertStringContainsString('"name"', $templateQuery['query']);

        // It should NOT select body
        $this->assertStringNotContainsString('"body"', $templateQuery['query']);
    }
}
