<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class PublicTemplatePreviewDosTest extends TestCase
{
    /**
     * Test that the certificate preview endpoint rejects excessively large content.
     */
    public function test_certificate_preview_rejects_large_content(): void
    {
        $largeContent = str_repeat('a', 100001); // 100KB + 1 byte

        $response = $this->postJson(route('public.preview.certificate'), [
            'type' => 'blade',
            'content' => $largeContent,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    /**
     * Test that the email preview endpoint rejects excessively large body.
     */
    public function test_email_preview_rejects_large_body(): void
    {
        $largeBody = str_repeat('a', 100001); // 100KB + 1 byte

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => 'Valid Subject',
            'body' => $largeBody,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    /**
     * Test that the email preview endpoint rejects excessively large subject.
     */
    public function test_email_preview_rejects_large_subject(): void
    {
        $largeSubject = str_repeat('a', 256); // 256 bytes

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => $largeSubject,
            'body' => 'Valid Body',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }
}
