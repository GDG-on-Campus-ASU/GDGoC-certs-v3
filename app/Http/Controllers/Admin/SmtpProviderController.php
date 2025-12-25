<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpProvider;
use Illuminate\Http\Request;

class SmtpProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // For admin, we primarily want to manage global SMTP providers,
        // but maybe we want to see all? Usually global managers manage global things.
        // The user said "Global SMTP Manager".
        $providers = SmtpProvider::with('user')
            ->where('is_global', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.smtp.index', compact('providers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.smtp.create');
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
            'is_global' => ['boolean'],
        ]);

        // Password encryption is handled by the model mutator
        $validated['user_id'] = auth()->id();
        $validated['is_global'] = $request->boolean('is_global');

        SmtpProvider::create($validated);

        return redirect()->route('admin.smtp.index')
            ->with('success', 'SMTP provider created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SmtpProvider $smtp)
    {
        // $smtp is the route parameter name, matched from resource
        return view('admin.smtp.edit', compact('smtp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SmtpProvider $smtp)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'is_global' => ['boolean'],
        ]);

        // Only update password if provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['is_global'] = $request->boolean('is_global');

        $smtp->update($validated);

        return redirect()->route('admin.smtp.index')
            ->with('success', 'SMTP provider updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SmtpProvider $smtp)
    {
        $smtp->delete();

        return redirect()->route('admin.smtp.index')
            ->with('success', 'SMTP provider deleted successfully.');
    }
}
