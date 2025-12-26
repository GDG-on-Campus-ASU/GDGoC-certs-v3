<?php

namespace Tests\Feature\Security;

use App\Jobs\ProcessCertificateRow;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class EmailTemplateRCETest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_execute_php_code_via_email_template()
    {
        // 1. Create a user
        $user = User::factory()->create([
            'role' => 'leader',
            'org_name' => 'Test Org',
        ]);

        $this->actingAs($user);

        // 2. Create an email template with malicious payload
        $proofPath = storage_path('app/rce_proof.txt');
        $payload = '@php file_put_contents("'.addslashes($proofPath).'", "RCE_SUCCESS"); @endphp';
        // Add some normal content to verify substitution still works
        $payload .= ' Hello {{ Recipient_Name }}';

        $emailTemplate = EmailTemplate::create([
            'user_id' => $user->id,
            'name' => 'Malicious Template',
            'subject' => 'Malicious Subject',
            'body' => $payload,
            'is_global' => false,
        ]);

        // 3. Create a certificate template
        $certTemplate = CertificateTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Cert',
            'content' => '<h1>Certificate</h1>',
            'is_global' => false,
        ]);

        // 4. Dispatch the job
        $rowData = [
            'recipient_name' => 'Safe User',
            'recipient_email' => 'victim@example.com',
            'state' => 'attending',
            'event_type' => 'workshop',
            'event_title' => 'Safe Event',
            'issue_date' => '2023-01-01',
        ];

        $certificateService = Mockery::mock(CertificateService::class);
        $certificateService->shouldReceive('generate')->andReturn('PDF_CONTENT');
        $certificateService->shouldReceive('store')->andReturn('certificates/test.pdf');

        Mail::fake();

        $job = new ProcessCertificateRow(
            $user->id,
            $rowData,
            $user->name,
            $user->org_name,
            $certTemplate->id,
            $emailTemplate->id
        );

        $job->handle($certificateService);

        // 5. Verify RCE did NOT happen
        $this->assertFileDoesNotExist($proofPath);

        // 6. Verify correct substitution happened in the mail
        Mail::assertSent(\App\Mail\SendCertificateMail::class, function ($mail) {
            // The body should contain the substituted name, but the PHP tags should remain as plain text (or simply not executed)
            return str_contains($mail->body, 'Hello Safe User')
                && str_contains($mail->body, '@php');
        });

        // Clean up (just in case)
        if (file_exists($proofPath)) {
            @unlink($proofPath);
        }
    }
}
