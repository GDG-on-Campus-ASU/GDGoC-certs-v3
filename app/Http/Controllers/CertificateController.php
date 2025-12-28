<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    /**
     * Show the form for creating a new certificate.
     */
    public function create()
    {
        $userCertTemplates = auth()->user()->certificateTemplates()->select('id', 'name')->get();
        $globalCertTemplates = CertificateTemplate::where('is_global', true)->select('id', 'name')->get();

        $userEmailTemplates = auth()->user()->emailTemplates()->select('id', 'name')->get();
        $globalEmailTemplates = EmailTemplate::where('is_global', true)->select('id', 'name')->get();

        return view('dashboard.certificates.create', compact(
            'userCertTemplates',
            'globalCertTemplates',
            'userEmailTemplates',
            'globalEmailTemplates'
        ));
    }

    /**
     * Store a newly created certificate in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'certificate_template_id' => ['required', 'exists:certificate_templates,id'],
            'email_template_id' => ['required', 'exists:email_templates,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'state' => ['required', Rule::in(['attending', 'completing'])],
            'event_type' => ['required', Rule::in(['workshop', 'course'])],
            'event_title' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
        ]);

        // Verify the certificate template is accessible (global or owned by user)
        $certTemplate = CertificateTemplate::findOrFail($validated['certificate_template_id']);
        if (! $certTemplate->is_global && $certTemplate->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to certificate template.');
        }

        // Verify the email template is accessible (global or owned by user)
        $emailTemplate = EmailTemplate::findOrFail($validated['email_template_id']);
        if (! $emailTemplate->is_global && $emailTemplate->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to email template.');
        }

        // Generate unique ID
        $uniqueId = Str::uuid()->toString();

        // Create certificate
        Certificate::create([
            'user_id' => auth()->id(),
            'certificate_template_id' => $validated['certificate_template_id'],
            'unique_id' => $uniqueId,
            'recipient_name' => $validated['recipient_name'],
            'recipient_email' => $validated['recipient_email'] ?? null,
            'state' => $validated['state'],
            'event_type' => $validated['event_type'],
            'event_title' => $validated['event_title'],
            'issue_date' => $validated['issue_date'],
            'issuer_name' => auth()->user()->name,
            'org_name' => auth()->user()->org_name ?? '',
        ]);

        return redirect()->route('dashboard.certificates.create')
            ->with('success', 'Certificate created successfully.');
    }
}
