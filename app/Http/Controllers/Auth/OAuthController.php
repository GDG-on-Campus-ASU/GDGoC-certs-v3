<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to OIDC provider for authentication
     */
    public function redirect(Request $request)
    {
        $settings = OidcSetting::getConfigured();

        if (!$settings) {
            return redirect()->route('login')->with('error', 'SSO authentication is not configured.');
        }

        // Generate state parameter for CSRF protection
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        // Generate nonce for OIDC
        $nonce = Str::random(32);
        $request->session()->put('oauth_nonce', $nonce);

        // Build the authorization URL
        $params = [
            'client_id' => $settings->client_id,
            'redirect_uri' => route('oauth.callback'),
            'response_type' => 'code',
            'scope' => $settings->scope ?? 'openid profile email',
            'state' => $state,
            'nonce' => $nonce,
        ];

        // Parse the login endpoint URL to handle existing query parameters
        $parsedUrl = parse_url($settings->login_endpoint_url);
        $existingParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $existingParams);
        }
        
        // Merge parameters (new params override existing ones)
        $allParams = array_merge($existingParams, $params);
        
        // Reconstruct the URL
        $authUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . 
                   ($parsedUrl['host'] ?? '') . 
                   ($parsedUrl['path'] ?? '') . 
                   '?' . http_build_query($allParams);

        return redirect($authUrl);
    }

    /**
     * Handle callback from OIDC provider
     */
    public function callback(Request $request)
    {
        $settings = OidcSetting::getConfigured();

        if (!$settings) {
            return redirect()->route('login')->with('error', 'SSO authentication is not configured.');
        }

        // Verify state parameter using hash_equals to prevent timing attacks
        $sessionState = $request->session()->get('oauth_state');
        if (!$sessionState || !hash_equals($sessionState, $request->state ?? '')) {
            return redirect()->route('login')->with('error', 'Invalid state parameter. Please try again.');
        }

        // TODO: Implement token exchange and user authentication
        // This is a placeholder for the full OAuth/OIDC callback flow
        // In a complete implementation, you would:
        // 1. Exchange the authorization code for tokens
        // 2. Validate the ID token
        // 3. Fetch user info
        // 4. Create or update the user
        // 5. Log them in

        return redirect()->route('login')->with('error', 'SSO callback handling is not fully implemented yet.');
    }
}
