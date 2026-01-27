<?php

namespace Tests\Feature\Security;

use App\Jobs\ProcessCertificateRow;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Models\SmtpProvider;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailConfigLeakTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_worker_process_does_not_leak_mail_from_configuration(): void
    {
        // Set initial default config
        Config::set('mail.from.address', 'default@example.com');
        Config::set('mail.from.name', 'Default Sender');

        // Setup User A with Custom SMTP
        $userA = User::factory()->create();
        $smtpA = SmtpProvider::create([
            'user_id' => $userA->id,
            'name' => 'Custom SMTP',
            'host' => 'smtp.custom.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'pass',
            'encryption' => 'tls',
            'from_address' => 'custom@userA.com',
            'from_name' => 'User A Custom',
        ]);

        $certTemplateA = CertificateTemplate::factory()->create(['user_id' => $userA->id]);
        $emailTemplateA = EmailTemplate::factory()->create(['user_id' => $userA->id]);

        // Setup User B with NO Custom SMTP (should use default)
        $userB = User::factory()->create();
        $certTemplateB = CertificateTemplate::factory()->create(['user_id' => $userB->id]);
        $emailTemplateB = EmailTemplate::factory()->create(['user_id' => $userB->id]);

        // Mock CertificateService
        $certificateService = \Mockery::mock(CertificateService::class);
        $certificateService->shouldReceive('generate')->andReturn('PDF CONTENT');
        $certificateService->shouldReceive('store')->andReturn('certificates/test.pdf');

        Mail::fake();

        // 1. Run Job for User A
        $jobA = new ProcessCertificateRow(
            userId: $userA->id,
            row: [
                'recipient_name' => 'Recipient A',
                'recipient_email' => 'recipientA@test.com',
                'state' => 'attending',
                'event_type' => 'workshop',
                'event_title' => 'Event A',
                'issue_date' => '2023-01-01',
            ],
            issuerName: 'Issuer A',
            orgName: 'Org A',
            certificateTemplateId: $certTemplateA->id,
            emailTemplateId: $emailTemplateA->id,
            smtpProviderId: $smtpA->id
        );

        $jobA->handle($certificateService);

        // Assert that the global config was NOT changed
        $this->assertEquals('default@example.com', Config::get('mail.from.address'));
        $this->assertEquals('Default Sender', Config::get('mail.from.name'));

        // 2. Run Job for User B (No SMTP provider passed)
        $jobB = new ProcessCertificateRow(
            userId: $userB->id,
            row: [
                'recipient_name' => 'Recipient B',
                'recipient_email' => 'recipientB@test.com',
                'state' => 'attending',
                'event_type' => 'workshop',
                'event_title' => 'Event B',
                'issue_date' => '2023-01-01',
            ],
            issuerName: 'Issuer B',
            orgName: 'Org B',
            certificateTemplateId: $certTemplateB->id,
            emailTemplateId: $emailTemplateB->id,
            smtpProviderId: null // Uses default
        );

        $jobB->handle($certificateService);

        // Assert that the global config is STILL default
        $this->assertEquals('default@example.com', Config::get('mail.from.address'), 'Config remained clean');
    }
}
