<?php

namespace Tests\Feature\Security;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

class CertificatePdfInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_data_is_sanitized_in_pdf_generation()
    {
        // Mock the Snappy PDF wrapper to inspect the HTML content
        $pdfMock = Mockery::mock('stdClass');
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();
        $pdfMock->shouldReceive('setOption')->andReturnSelf(); // Allow setOption
        $pdfMock->shouldReceive('output')->andReturn('PDF_CONTENT');

        // We capture the arguments to verify them later
        $capturedHtml = null;
        $pdfMock->shouldReceive('loadHTML')->with(Mockery::capture($capturedHtml))->andReturnSelf();

        App::instance('snappy.pdf.wrapper', $pdfMock);

        // Create a user and template
        $user = User::factory()->create();
        $template = CertificateTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'content' => '<h1>Certificate for {Recipient_Name}</h1>',
            'type' => 'blade',
            'is_global' => false,
        ]);

        // Create a certificate with malicious data
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'certificate_template_id' => $template->id,
            'unique_id' => 'test-uuid',
            'recipient_name' => '<script>alert("XSS")</script>',
            'recipient_email' => 'test@example.com',
            'state' => 'attending',
            'event_type' => 'workshop',
            'event_title' => 'Test Event',
            'issue_date' => now(),
            'issuer_name' => 'Issuer',
            'org_name' => 'Org',
        ]);

        $service = new CertificateService;
        $service->generate($certificate);

        // Assertions
        $this->assertNotNull($capturedHtml, 'loadHTML should have been called');
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $capturedHtml, 'HTML should not contain unescaped script tags');
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $capturedHtml, 'HTML should contain escaped script tags');
    }
}
