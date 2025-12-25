<?php

namespace App\Jobs;

use App\Mail\SendCertificateMail;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Services\CertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ProcessCertificateRow implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public array $row,
        public string $issuerName,
        public string $orgName,
        public int $certificateTemplateId,
        public int $emailTemplateId,
        public ?int $smtpProviderId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CertificateService $certificateService): void
    {
        // Find the CertificateTemplate
        $certTemplate = CertificateTemplate::findOrFail($this->certificateTemplateId);

        // Find the EmailTemplate
        $emailTemplate = EmailTemplate::findOrFail($this->emailTemplateId);

        // Generate unique ID
        $uniqueId = Str::uuid()->toString();

        // Create certificate record
        $certificate = Certificate::create([
            'user_id' => $this->userId,
            'certificate_template_id' => $this->certificateTemplateId,
            'unique_id' => $uniqueId,
            'recipient_name' => $this->row['recipient_name'],
            'recipient_email' => $this->row['recipient_email'] ?? null,
            'state' => $this->row['state'],
            'event_type' => $this->row['event_type'],
            'event_title' => $this->row['event_title'],
            'issue_date' => $this->row['issue_date'],
            'issuer_name' => $this->issuerName,
            'org_name' => $this->orgName,
        ]);

        // Generate PDF
        $pdfData = $certificateService->generate($certificate);

        // Store PDF
        $path = 'certificates/'.$certificate->unique_id.'.pdf';
        \Illuminate\Support\Facades\Storage::put($path, $pdfData);
        $certificate->update(['file_path' => $path]);

        // Configure Mailer - check for user's SMTP setting
        $mailerName = 'smtp'; // default mailer

        if ($this->smtpProviderId) {
            $smtpSetting = \App\Models\SmtpProvider::find($this->smtpProviderId);

            if ($smtpSetting) {
                // Configure custom SMTP mailer
                Config::set('mail.mailers.custom_smtp', [
                    'transport' => 'smtp',
                    'host' => $smtpSetting->host,
                    'port' => $smtpSetting->port,
                    'username' => $smtpSetting->username,
                    'password' => $smtpSetting->password,
                    'encryption' => $smtpSetting->encryption,
                    'timeout' => null,
                ]);

                Config::set('mail.from.address', $smtpSetting->from_address);
                Config::set('mail.from.name', $smtpSetting->from_name);

                $mailerName = 'custom_smtp';
            }
        }

        // Render Email
        $replacements = [
            'Recipient_Name' => $certificate->recipient_name,
            'Event_Title' => $certificate->event_title,
            'Org_Name' => $certificate->org_name,
            'state' => $certificate->state,
            'event_type' => $certificate->event_type,
            'issue_date' => $certificate->issue_date->toFormattedDateString(),
            'issuer_name' => $certificate->issuer_name,
            'unique_id' => $certificate->unique_id,
        ];

        // Securely replace variables in email body and subject
        // We do NOT use Blade::render as it allows arbitrary PHP execution from user input
        $body = $emailTemplate->body;
        $subject = $emailTemplate->subject;

        foreach ($replacements as $key => $value) {
            // Support {{ Key }} and {{Key}} and {{ $Key }} formats
            $search = [
                '{{'.$key.'}}',
                '{{ '.$key.' }}',
                '{{$'.$key.'}}',
                '{{ $'.$key.' }}',
            ];

            // Escape the value to prevent HTML injection in the email body
            // We use htmlspecialchars because the body is treated as HTML
            $safeValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');

            $body = str_replace($search, $safeValue, $body);
            $subject = str_replace($search, $value, $subject); // Subject is plain text, but email headers handle it.
        }

        // Send Email (only if recipient_email is provided)
        if ($certificate->recipient_email) {
            Mail::mailer($mailerName)
                ->to($certificate->recipient_email)
                ->send(new SendCertificateMail($subject, $body, $pdfData));
        }
    }
}
