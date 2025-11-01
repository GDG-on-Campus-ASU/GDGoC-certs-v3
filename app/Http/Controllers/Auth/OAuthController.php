<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /**
     * Redirect to OIDC provider for authentication
     */
    public function redirect()
    {
        // TODO: Configure OIDC provider in a later step
        // return Socialite::driver('oidc')->redirect();

        return redirect()->route('login')->with('error', 'OAuth provider not configured yet.');
    }

    /**
     * Handle callback from OIDC provider
     */
    public function callback(Request $request)
    {
        // TODO: Implement OIDC callback logic in a later step
        // $user = Socialite::driver('oidc')->user();

        return redirect()->route('login')->with('error', 'OAuth provider not configured yet.');
    }
}
