<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrgNameIsSet
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if user is not authenticated or already has org_name set
        if (!$user || $user->org_name) {
            return $next($request);
        }

        // Redirect to profile page to complete org_name
        return redirect()->route('profile.edit')->with('info', 'Please complete your organization name to continue.');
    }
}
