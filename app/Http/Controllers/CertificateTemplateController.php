<?php

namespace App\Http\Controllers;

use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            $content = str_replace(['{{ $'.$key.' }}', '{{$'.$key.'}}', '{{ '.$key.' }}', '{{'.$key.'}}'], $value, $content);
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
        Gate::authorize('viewAny', CertificateTemplate::class);

        $userTemplates = auth()->user()->certificateTemplates()
            ->select('id', 'user_id', 'name', 'type', 'created_at', 'original_template_id')
            ->get();
        $globalTemplates = CertificateTemplate::where('is_global', true)
            ->select('id', 'name', 'type', 'created_at')
            ->get();

        return view('dashboard.templates.certificates.index', compact('userTemplates', 'globalTemplates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', CertificateTemplate::class);

        return view('dashboard.templates.certificates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', CertificateTemplate::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
        ]);

        CertificateTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'is_global' => false,
        ]);

        return redirect()->route('dashboard.templates.certificates.index')
            ->with('success', 'Certificate template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CertificateTemplate $certificateTemplate)
    {
        Gate::authorize('update', $certificateTemplate);

        return view('dashboard.templates.certificates.edit', compact('certificateTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        Gate::authorize('update', $certificateTemplate);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
        ]);

        $certificateTemplate->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
        ]);

        return redirect()->route('dashboard.templates.certificates.index')
            ->with('success', 'Certificate template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CertificateTemplate $certificateTemplate)
    {
        Gate::authorize('delete', $certificateTemplate);

        $certificateTemplate->delete();

        return redirect()->route('dashboard.templates.certificates.index')
            ->with('success', 'Certificate template deleted successfully.');
    }

    /**
     * Clone a template.
     */
    public function clone(CertificateTemplate $certificateTemplate)
    {
        Gate::authorize('clone', $certificateTemplate);
        Gate::authorize('view', $certificateTemplate);

        $cloned = $certificateTemplate->replicate();
        $cloned->user_id = auth()->id();
        $cloned->is_global = false;
        $cloned->original_template_id = $certificateTemplate->id;
        $cloned->name = $certificateTemplate->name.' (Copy)';
        $cloned->save();

        return redirect()->route('dashboard.templates.certificates.index')
            ->with('success', 'Template cloned successfully.');
    }

    /**
     * Reset a template to its original.
     */
    public function reset(CertificateTemplate $certificateTemplate)
    {
        Gate::authorize('reset', $certificateTemplate);
        Gate::authorize('update', $certificateTemplate);

        if (! $certificateTemplate->original_template_id) {
            return redirect()->route('dashboard.templates.certificates.index')
                ->with('error', 'This template cannot be reset as it was not cloned from a global template.');
        }

        $original = CertificateTemplate::find($certificateTemplate->original_template_id);

        if (! $original) {
            return redirect()->route('dashboard.templates.certificates.index')
                ->with('error', 'The original template no longer exists.');
        }

        $certificateTemplate->content = $original->content;
        $certificateTemplate->type = $original->type;
        $certificateTemplate->save();

        return redirect()->route('dashboard.templates.certificates.index')
            ->with('success', 'Template reset to original successfully.');
    }
}
