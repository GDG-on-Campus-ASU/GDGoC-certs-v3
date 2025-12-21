<?php

namespace Tests\Feature\Security;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use App\Services\CertificateService;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Mockery;

class WkhtmltopdfSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_generation_disables_dangerous_options()
    {
        // Mock the Snappy PDF wrapper
        $pdfMock = Mockery::mock('Barryvdh\Snappy\PdfWrapper');

        // Expect loadHTML to be called
        $pdfMock->shouldReceive('loadHTML')->andReturnSelf();

        // Expect setPaper to be called (existing functionality)
        $pdfMock->shouldReceive('setPaper')->andReturnSelf();

        // Crucial security expectations
        $pdfMock->shouldReceive('setOption')
            ->with('disable-javascript', true)
            ->once()
            ->andReturnSelf();

        $pdfMock->shouldReceive('setOption')
            ->with('disable-local-file-access', true)
            ->once()
            ->andReturnSelf();

        // We expect output to be called
        $pdfMock->shouldReceive('output')->andReturn('pdf-content');

        // Swap the instance in the container
        App::instance('snappy.pdf.wrapper', $pdfMock);

        // Create data
        $user = User::factory()->create();
        $template = CertificateTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Template',
            'content' => '<html><body><h1>{{ Recipient_Name }}</h1><script>alert("XSS")</script></body></html>',
            'type' => 'blade',
            'is_global' => false,
        ]);

        $certificate = Certificate::factory()->create([
            'certificate_template_id' => $template->id,
            'recipient_name' => 'John Doe',
        ]);

        // Run the service
        $service = new CertificateService();
        $service->generate($certificate);
    }
}
