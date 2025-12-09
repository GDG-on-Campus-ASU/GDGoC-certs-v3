<?php

namespace Tests\Feature\Leader;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
            'role' => 'leader',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('stats');
    }

    public function test_dashboard_displays_correct_statistics(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
            'role' => 'leader',
        ]);

        // Create test data
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);
        
        // Create 3 issued certificates
        Certificate::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'issued',
            'recipient_email' => 'test@example.com',
            'certificate_template_id' => $template->id,
        ]);

        // Create 2 revoked certificates
        Certificate::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'revoked',
            'recipient_email' => 'test@example.com',
            'certificate_template_id' => $template->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        
        $stats = $response->viewData('stats');
        
        $this->assertEquals(5, $stats['total_certificates']);
        $this->assertEquals(3, $stats['active_certificates']);
        $this->assertEquals(5, $stats['emails_sent']);
        $this->assertEquals(2, $stats['revoked_certificates']);
    }

    public function test_dashboard_includes_quick_links(): void
    {
        $user = User::factory()->create([
            'org_name' => 'Test Organization',
            'role' => 'leader',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Quick Links');
        $response->assertSee('Certificates');
        $response->assertSee('Private Configuration');
    }
}
