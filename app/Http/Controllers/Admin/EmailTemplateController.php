<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

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
        $templates = EmailTemplate::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.templates.email.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.templates.email.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_global' => ['boolean'],
        ]);

        EmailTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.email.index')
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        return view('admin.templates.email.edit', compact('emailTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_global' => ['boolean'],
        ]);

        $emailTemplate->update([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.email.index')
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->route('admin.templates.email.index')
            ->with('success', 'Email template deleted successfully.');
    }
}
