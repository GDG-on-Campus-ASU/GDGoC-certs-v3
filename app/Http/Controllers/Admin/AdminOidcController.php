<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OidcSetting;
use Illuminate\Http\Request;

class AdminOidcController extends Controller
{
    /**
     * Show the OIDC settings form.
     */
    public function edit()
    {
        $settings = OidcSetting::first() ?? new OidcSetting;

        return view('admin.oidc.edit', compact('settings'));
    }

    /**
     * Update or create OIDC settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string'],
            'scope' => ['nullable', 'string', 'max:255'],
            'login_endpoint_url' => ['nullable', 'url', 'max:255'],
            'userinfo_endpoint_url' => ['nullable', 'url', 'max:255'],
            'token_validation_endpoint_url' => ['nullable', 'url', 'max:255'],
            'end_session_endpoint_url' => ['nullable', 'url', 'max:255'],
            'identity_key' => ['nullable', 'string', 'max:255'],
            'link_existing_users' => ['boolean'],
            'create_new_users' => ['boolean'],
            'redirect_on_expiry' => ['boolean'],
        ]);

        // Convert checkboxes to booleans
        $validated['link_existing_users'] = $request->has('link_existing_users');
        $validated['create_new_users'] = $request->has('create_new_users');
        $validated['redirect_on_expiry'] = $request->has('redirect_on_expiry');

        OidcSetting::updateOrCreate(
            ['id' => 1],
            $validated
        );

        return redirect()->route('admin.oidc.edit')
            ->with('success', 'OIDC settings updated successfully.');
    }
}
