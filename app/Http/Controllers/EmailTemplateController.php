<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmailTemplateController extends Controller
{
    /**
     * Preview the email template.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
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

        $subject = $validated['subject'];
        $body = $validated['body'];

        foreach ($replacements as $key => $value) {
            // Replace {{ $key }} and {{ $key }} and {{$key}}
            $subject = str_replace(['{{ $' . $key . ' }}', '{{$' . $key . '}}', '{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $subject);
            $body = str_replace(['{{ $' . $key . ' }}', '{{$' . $key . '}}', '{{ ' . $key . ' }}', '{{' . $key . '}}'], $value, $body);
        }

        return response()->json([
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', EmailTemplate::class);

        $userTemplates = auth()->user()->emailTemplates;
        $globalTemplates = EmailTemplate::where('is_global', true)->get();

        return view('dashboard.templates.email.index', compact('userTemplates', 'globalTemplates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', EmailTemplate::class);

        return view('dashboard.templates.email.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', EmailTemplate::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        EmailTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'is_global' => false,
        ]);

        return redirect()->route('dashboard.templates.email.index')
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        Gate::authorize('update', $emailTemplate);

        return view('dashboard.templates.email.edit', compact('emailTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        Gate::authorize('update', $emailTemplate);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $emailTemplate->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        return redirect()->route('dashboard.templates.email.index')
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        Gate::authorize('delete', $emailTemplate);

        $emailTemplate->delete();

        return redirect()->route('dashboard.templates.email.index')
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * Clone a template.
     */
    public function clone(EmailTemplate $emailTemplate)
    {
        Gate::authorize('clone', $emailTemplate);
        Gate::authorize('view', $emailTemplate);

        $cloned = $emailTemplate->replicate();
        $cloned->user_id = auth()->id();
        $cloned->is_global = false;
        $cloned->original_template_id = $emailTemplate->id;
        $cloned->name = $emailTemplate->name.' (Copy)';
        $cloned->save();

        return redirect()->route('dashboard.templates.email.index')
            ->with('success', 'Template cloned successfully.');
    }

    /**
     * Reset a template to its original.
     */
    public function reset(EmailTemplate $emailTemplate)
    {
        Gate::authorize('reset', $emailTemplate);
        Gate::authorize('update', $emailTemplate);

        if (! $emailTemplate->original_template_id) {
            return redirect()->route('dashboard.templates.email.index')
                ->with('error', 'This template cannot be reset as it was not cloned from a global template.');
        }

        $original = EmailTemplate::find($emailTemplate->original_template_id);

        if (! $original) {
            return redirect()->route('dashboard.templates.email.index')
                ->with('error', 'The original template no longer exists.');
        }

        $emailTemplate->subject = $original->subject;
        $emailTemplate->body = $original->body;
        $emailTemplate->save();

        return redirect()->route('dashboard.templates.email.index')
            ->with('success', 'Template reset to original successfully.');
    }
}
