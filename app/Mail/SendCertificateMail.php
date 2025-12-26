<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendCertificateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailSubject;

    public $body;

    public $pdfData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $body,
        string $pdfData
    ) {
        $this->mailSubject = $subject;
        $this->body = $body;
        $this->pdfData = $pdfData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Using htmlString to render raw HTML body content
        // The body is already rendered by Blade::render in the Job, so it's HTML string.
        return new Content(
            htmlString: $this->body,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfData, 'certificate.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
