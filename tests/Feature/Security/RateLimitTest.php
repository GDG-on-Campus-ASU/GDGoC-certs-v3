<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    /**
     * Test that public certificate validation routes are rate limited.
     */
    public function test_public_routes_are_rate_limited(): void
    {
        // Use a route that we know should be rate limited
        $url = route('public.validate.index');

        $route = Route::getRoutes()->getByName('public.validate.index');
        $middleware = $route->gatherMiddleware();

        // Check if 'throttle' or 'throttle:60,1' is in the middleware list
        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }

        $this->assertTrue($hasThrottle, 'Throttle middleware is not applied to public.validate.index');

        // Also check the download route
        $route = Route::getRoutes()->getByName('public.certificate.download');
        $middleware = $route->gatherMiddleware();

        $hasThrottle = false;
        foreach ($middleware as $m) {
            if (str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }

        $this->assertTrue($hasThrottle, 'Throttle middleware is not applied to public.certificate.download');
    }
}
