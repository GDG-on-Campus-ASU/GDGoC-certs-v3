<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTemplatePreviewDosTest extends TestCase
{
    public function test_certificate_preview_rejects_large_content()
    {
        // 100,001 characters
        $largeContent = Str::repeat('a', 100001);

        $response = $this->postJson(route('public.preview.certificate'), [
            'content' => $largeContent,
            'type' => 'blade',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_email_preview_rejects_large_body()
    {
        // 100,001 characters
        $largeBody = Str::repeat('a', 100001);

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => 'Normal Subject',
            'body' => $largeBody,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_email_preview_rejects_large_subject()
    {
        // 256 characters
        $largeSubject = Str::repeat('a', 256);

        $response = $this->postJson(route('public.preview.email'), [
            'subject' => $largeSubject,
            'body' => 'Normal Body',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }
}
