<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    /**
     * Test that essential security headers are present in the response.
     */
    public function test_security_headers_are_present()
    {
        $response = $this->get('/');

        $response->assertSuccessful();

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Test that information leaking headers are removed.
     */
    public function test_x_powered_by_header_is_removed()
    {
        $response = $this->get('/');

        // Note: PHPUnit assertions or Laravel's response assertions can be used.
        // assertHeaderMissing is available in newer Laravel versions, checking manually for compatibility.
        $this->assertFalse(
            $response->headers->has('X-Powered-By'),
            "The X-Powered-By header should be removed to prevent information leakage."
        );
    }
}
