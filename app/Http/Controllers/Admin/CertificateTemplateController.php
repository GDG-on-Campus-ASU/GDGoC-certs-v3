<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    /**
     * Preview the certificate template.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
        ]);

        $replacements = [
            'Recipient_Name' => 'John Doe',
            'Event_Title' => 'Certificate Award Ceremony',
            'Org_Name' => 'GDG on Campus',
            'state' => 'New York',
            'event_type' => 'Workshop',
            'issue_date' => now()->toFormattedDateString(),
            'issuer_name' => 'Jane Smith',
            'unique_id' => '123e4567-e89b-12d3-a456-426614174000',
        ];

        $content = $validated['content'];

        foreach ($replacements as $key => $value) {
            // Replace {{ $key }} and {{ $key }} and {{$key}}
            $content = str_replace(['{{ $' . $key . ' }}', '{{$' . $key . '}}', '{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $content);
        }

        return response()->json([
            'content' => $content,
            'type' => $validated['type'],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = CertificateTemplate::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.templates.certificates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.templates.certificates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
            'is_global' => ['boolean'],
        ]);

        CertificateTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CertificateTemplate $certificateTemplate)
    {
        return view('admin.templates.certificates.edit', compact('certificateTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
            'is_global' => ['boolean'],
        ]);

        $certificateTemplate->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CertificateTemplate $certificateTemplate)
    {
        $certificateTemplate->delete();

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template deleted successfully.');
    }
}
