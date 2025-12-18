<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class PublicCertificatePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_download_caches_generated_pdf()
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $template = CertificateTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'content' => '<h1>Certificate for {Recipient_Name}</h1>',
            'type' => 'blade',
        ]);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'unique_id' => 'test-unique-id',
            'recipient_name' => 'John Doe',
            'state' => 'attending',
            'event_type' => 'workshop',
            'event_title' => 'Performance Workshop',
            'issue_date' => now(),
            'issuer_name' => 'Bolt',
            'org_name' => 'Speed Corp',
            'status' => 'issued',
        ]);

        // Mock CertificateService to count calls to generate
        $mockService = Mockery::mock(CertificateService::class);
        $mockService->shouldReceive('generate')
            ->once() // Expect it to be called exactly once
            ->andReturn('PDF CONTENT');

        // Bind the mock
        $this->app->instance(CertificateService::class, $mockService);

        // First request - should generate PDF and cache it
        $response1 = $this->get(route('public.certificate.download', $certificate->unique_id));
        $response1->assertOk();
        $response1->assertHeader('Content-Type', 'application/pdf');

        // Verify that the file was saved to storage
        $filename = 'certificates/' . $certificate->unique_id . '.pdf';
        Storage::disk('local')->assertExists($filename);

        // Refresh certificate to verify DB update
        $certificate->refresh();
        $this->assertEquals($filename, $certificate->file_path);

        // Second request - should serve from cache (mock should NOT be called again)
        $response2 = $this->get(route('public.certificate.download', $certificate->unique_id));
        $response2->assertOk();
        $response2->assertHeader('Content-Type', 'application/pdf');
    }
}
