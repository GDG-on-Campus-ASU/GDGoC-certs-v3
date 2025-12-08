<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicCertificateRoutingTest extends TestCase
{
    /**
     * Test that public certificate validation index route exists.
     */
    public function test_public_validate_index_route_exists(): void
    {
        $url = route('public.validate.index');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/public', $url);
    }

    /**
     * Test that public certificate validation query route exists.
     */
    public function test_public_validate_query_route_exists(): void
    {
        $url = route('public.validate.query');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/validate', $url);
    }

    /**
     * Test that public certificate show route exists.
     */
    public function test_public_certificate_show_route_exists(): void
    {
        $url = route('public.certificate.show', ['unique_id' => 'test-123']);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/c/test-123', $url);
    }

    /**
     * Test that public certificate download route exists.
     */
    public function test_public_certificate_download_route_exists(): void
    {
        $url = route('public.certificate.download', ['unique_id' => 'test-456']);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/public/c/test-456/download', $url);
    }

    /**
     * Test that the public validation index page is accessible.
     */
    public function test_public_validate_index_page_accessible(): void
    {
        $response = $this->get(route('public.validate.index'));
        $response->assertStatus(200);
    }
}
