<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OidcSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    /**
     * Redirect to OIDC provider for authentication
     */
    public function redirect(Request $request)
    {
        $settings = OidcSetting::getConfigured();

        if (! $settings) {
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
        $authUrl = ($parsedUrl['scheme'] ?? 'https').'://'.
                   ($parsedUrl['host'] ?? '').
                   ($parsedUrl['path'] ?? '').
                   '?'.http_build_query($allParams);

        return redirect($authUrl);
    }

    /**
     * Handle callback from OIDC provider
     */
    public function callback(Request $request)
    {
        $settings = OidcSetting::getConfigured();

        if (! $settings) {
            return redirect()->route('login')->with('error', 'SSO authentication is not configured.');
        }

        // Verify state parameter using hash_equals to prevent timing attacks
        $sessionState = $request->session()->get('oauth_state');
        if (! $sessionState || ! hash_equals($sessionState, $request->state ?? '')) {
            return redirect()->route('login')->with('error', 'Invalid state parameter. Please try again.');
        }

        // Check for error in callback
        if ($request->has('error')) {
            return redirect()->route('login')->with('error', 'OIDC Error: '.($request->error_description ?? $request->error));
        }

        try {
            // 1. Exchange the authorization code for tokens
            $tokenResponse = Http::asForm()->post($settings->token_endpoint_url, [
                'grant_type' => 'authorization_code',
                'client_id' => $settings->client_id,
                'client_secret' => $settings->client_secret,
                'redirect_uri' => route('oauth.callback'),
                'code' => $request->code,
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('OIDC Token Exchange Failed', ['response' => $tokenResponse->body()]);

                return redirect()->route('login')->with('error', 'Failed to exchange authentication code for token.');
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $idToken = $tokens['id_token'] ?? null;

            if (! $accessToken) {
                return redirect()->route('login')->with('error', 'No access token received from OIDC provider.');
            }

            // 2. Validate ID Token (Basic Audience Check)
            if ($idToken) {
                $parts = explode('.', $idToken);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $parts[1]))), true);
                    if ($payload) {
                        $aud = $payload['aud'] ?? null;
                        // aud can be a string or an array
                        $isValidAud = false;
                        if (is_string($aud) && $aud === $settings->client_id) {
                            $isValidAud = true;
                        } elseif (is_array($aud) && in_array($settings->client_id, $aud)) {
                            $isValidAud = true;
                        }

                        if (! $isValidAud) {
                            Log::warning('OIDC ID Token Audience Mismatch', ['aud' => $aud, 'expected' => $settings->client_id]);
                            // We don't block here as we rely on UserInfo, but logging is good practice
                        }
                    }
                }
            }

            // 3. Fetch user info
            $userInfoResponse = Http::withToken($accessToken)->get($settings->userinfo_endpoint_url);

            if (! $userInfoResponse->successful()) {
                Log::error('OIDC User Info Fetch Failed', ['response' => $userInfoResponse->body()]);

                return redirect()->route('login')->with('error', 'Failed to fetch user information.');
            }

            $userInfo = $userInfoResponse->json();

            // Check if email is verified
            // Security: We must ensure the email is verified by the provider to prevent account takeover
            // if an attacker creates an unverified account with a victim's email on the IdP.
            // We fail closed if the claim is missing or false.
            if (! isset($userInfo['email_verified']) || $userInfo['email_verified'] !== true) {
                return redirect()->route('login')->with('error', 'Your email address is not verified by the identity provider. Please verify your email and try again.');
            }

            // Determine the unique user identifier
            $identifierKey = $settings->identity_key ?? 'email';
            $userIdentifier = $userInfo[$identifierKey] ?? null;

            if (! $userIdentifier) {
                return redirect()->route('login')->with('error', "User identifier field '{$identifierKey}' is missing in the user info response.");
            }

            // 3. Find or Create User
            // First check if a user with this oauth_id exists
            $user = User::where('oauth_provider', 'oidc')
                ->where('oauth_id', $userInfo['sub'] ?? $userIdentifier)
                ->first();

            if (! $user) {
                // If not found by oauth_id, check by email if linking is enabled
                if ($settings->link_existing_users && isset($userInfo['email'])) {
                    $user = User::where('email', $userInfo['email'])->first();

                    if ($user) {
                        // Link the existing user
                        $user->oauth_provider = 'oidc';
                        $user->oauth_id = $userInfo['sub'] ?? $userIdentifier;
                        $user->save();
                    }
                }
            }

            if (! $user) {
                // If user still not found, check if we can create a new one
                if ($settings->create_new_users) {
                    $email = $userInfo['email'] ?? null;
                    if (! $email) {
                        return redirect()->route('login')->with('error', 'Email is required to create a new user account.');
                    }

                    $user = User::forceCreate([
                        'name' => $userInfo['name'] ?? $userInfo['preferred_username'] ?? explode('@', $email)[0],
                        'email' => $email,
                        'password' => bcrypt(Str::random(32)), // Random password since they use SSO
                        'oauth_provider' => 'oidc',
                        'oauth_id' => $userInfo['sub'] ?? $userIdentifier,
                        'status' => 'active', // Default status
                    ]);
                } else {
                    return redirect()->route('login')->with('error', 'User account not found and automatic creation is disabled.');
                }
            }

            if ($user->status !== 'active') {
                return redirect()->route('login')->with('error', 'Your account is currently inactive.');
            }

            // 4. Log them in
            Auth::login($user);

            // Clean up session
            $request->session()->forget(['oauth_state', 'oauth_nonce']);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            Log::error('OIDC Callback Exception', ['exception' => $e]);

            return redirect()->route('login')->with('error', 'An error occurred during authentication.');
        }
    }
}
