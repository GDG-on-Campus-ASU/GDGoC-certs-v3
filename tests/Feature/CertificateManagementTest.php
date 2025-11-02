<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_certificates(): void
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);

        Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.certificates.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_revoke_their_certificate(): void
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);

        $certificate = Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user)->post(
            route('dashboard.certificates.revoke', $certificate),
            ['revocation_reason' => 'Test revocation']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'status' => 'revoked',
            'revocation_reason' => 'Test revocation',
        ]);
    }

    public function test_user_cannot_revoke_another_users_certificate(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user2->id]);

        $certificate = Certificate::factory()->create([
            'user_id' => $user2->id,
            'certificate_template_id' => $template->id,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($user1)->post(
            route('dashboard.certificates.revoke', $certificate),
            ['revocation_reason' => 'Test revocation']
        );

        $response->assertStatus(403);
    }

    public function test_certificate_template_relationship_exists(): void
    {
        $user = User::factory()->create();
        $template = CertificateTemplate::factory()->create(['user_id' => $user->id]);

        $certificate = Certificate::factory()->create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
        ]);

        $this->assertNotNull($certificate->certificateTemplate);
        $this->assertEquals($template->id, $certificate->certificateTemplate->id);
    }
}
