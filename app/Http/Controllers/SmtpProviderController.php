<?php

namespace App\Http\Controllers;

use App\Models\SmtpProvider;
use Illuminate\Http\Request;

class SmtpProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $providers = auth()->user()->smtpProviders()->orderBy('created_at', 'desc')->get();

        return view('dashboard.smtp.index', compact('providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.smtp.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        // Password encryption is handled by the model mutator
        $validated['user_id'] = auth()->id();

        SmtpProvider::create($validated);

        return redirect()->route('dashboard.smtp.index')
            ->with('success', 'SMTP provider added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SmtpProvider $smtpProvider)
    {
        // Authorize that the user owns this SMTP provider
        if ($smtpProvider->user_id !== auth()->id()) {
            abort(403);
        }

        return view('dashboard.smtp.edit', compact('smtpProvider'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmtpProvider $smtpProvider)
    {
        // Authorize that the user owns this SMTP provider
        if ($smtpProvider->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
        ]);

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $smtpProvider->update($validated);

        return redirect()->route('dashboard.smtp.index')
            ->with('success', 'SMTP provider updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmtpProvider $smtpProvider)
    {
        // Authorize that the user owns this SMTP provider
        if ($smtpProvider->user_id !== auth()->id()) {
            abort(403);
        }

        $smtpProvider->delete();

        return redirect()->route('dashboard.smtp.index')
            ->with('success', 'SMTP provider deleted successfully.');
    }
}
