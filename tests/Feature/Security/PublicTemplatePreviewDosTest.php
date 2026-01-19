<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class PublicTemplatePreviewDosTest extends TestCase
{
    /**
     * Test that the public preview endpoint rejects excessively large payloads.
     */
    public function test_public_certificate_preview_rejects_large_payload()
    {
        // 2MB payload
        $largeContent = str_repeat('a', 2 * 1024 * 1024);

        $response = $this->postJson(route('public.preview.certificate'), [
            'content' => $largeContent,
            'type' => 'svg',
        ]);

        // Should return 422 Unprocessable Entity due to validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    /**
     * Test that the public email preview endpoint rejects excessively large payloads.
     */
    public function test_public_email_preview_rejects_large_payload()
    {
        // 2MB payload
        $largeBody = str_repeat('a', 2 * 1024 * 1024);

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => 'Test Subject',
            'body' => $largeBody,
        ]);

        // Should return 422 Unprocessable Entity due to validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }
}
