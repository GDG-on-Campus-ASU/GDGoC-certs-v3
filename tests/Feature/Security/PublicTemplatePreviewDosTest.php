<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTemplatePreviewDosTest extends TestCase
{
    /**
     * Test that certificate preview rejects excessively large content.
     */
    public function test_certificate_preview_rejects_large_content()
    {
        // 100KB limit
        $largeContent = Str::repeat('a', 100001);

        $response = $this->postJson(route('public.preview.certificate'), [
            'content' => $largeContent,
            'type' => 'blade',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /**
     * Test that email preview rejects excessively large body.
     */
    public function test_email_preview_rejects_large_body()
    {
        // 100KB limit
        $largeBody = Str::repeat('a', 100001);

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => 'Normal Subject',
            'body' => $largeBody,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    /**
     * Test that email preview rejects excessively large subject.
     */
    public function test_email_preview_rejects_large_subject()
    {
        // 255 chars limit
        $largeSubject = Str::repeat('a', 256);

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => $largeSubject,
            'body' => 'Normal Body',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }
}
