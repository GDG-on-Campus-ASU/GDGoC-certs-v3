<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCertificateRow;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class BulkCertificateController extends Controller
{
    /**
     * Show the form for bulk certificate upload.
     */
    public function create()
    {
        $userCertTemplates = auth()->user()->certificateTemplates()->select('id', 'name')->get();
        $globalCertTemplates = CertificateTemplate::where('is_global', true)->select('id', 'name')->get();

        $userEmailTemplates = auth()->user()->emailTemplates()->select('id', 'name')->get();
        $globalEmailTemplates = EmailTemplate::where('is_global', true)->select('id', 'name')->get();

        return view('dashboard.certificates.bulk', compact(
            'userCertTemplates',
            'globalCertTemplates',
            'userEmailTemplates',
            'globalEmailTemplates'
        ));
    }

    /**
     * Process the bulk certificate upload.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'certificate_template_id' => ['required', 'exists:certificate_templates,id'],
            'email_template_id' => ['required', 'exists:email_templates,id'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // Max 10MB
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

        // Parse CSV file
        $file = $request->file('csv_file');
        $csvData = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            // Read the header row
            $headers = fgetcsv($handle);
            // Read each data row
            while (($row = fgetcsv($handle)) !== false) {
                $csvData[] = $row;
            }
            fclose($handle);
        } else {
            return back()->withErrors(['csv_file' => 'Unable to open the uploaded CSV file.']);
        }

        // Expected headers: recipient_name, recipient_email, state, event_type, event_title, issue_date
        $expectedHeaders = ['recipient_name', 'recipient_email', 'state', 'event_type', 'event_title', 'issue_date'];

        // Validate headers
        $normalizedHeaders = array_map('trim', array_map('strtolower', $headers));
        foreach ($expectedHeaders as $expectedHeader) {
            if (! in_array($expectedHeader, $normalizedHeaders)) {
                return back()->withErrors(['csv_file' => "Missing required column: {$expectedHeader}"]);
            }
        }

        // Create header index map
        $headerMap = array_flip($normalizedHeaders);

        $processedCount = 0;
        $errors = [];

        // Optimization: Fetch common user data once outside the loop to avoid N+1 queries
        $user = auth()->user();
        $userName = $user->name;
        $userOrgName = $user->org_name ?? '';
        $userId = $user->id;
        $smtpProviderId = $user->smtpProviders()->first()?->id;

        foreach ($csvData as $lineNumber => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                // Map row data
                $rowData = [
                    'recipient_name' => $row[$headerMap['recipient_name']] ?? '',
                    'recipient_email' => isset($row[$headerMap['recipient_email']]) ? (trim($row[$headerMap['recipient_email']]) ?: null) : null,
                    'state' => $row[$headerMap['state']] ?? '',
                    'event_type' => $row[$headerMap['event_type']] ?? '',
                    'event_title' => $row[$headerMap['event_title']] ?? '',
                    'issue_date' => $row[$headerMap['issue_date']] ?? '',
                ];

                // Basic validation
                if (empty($rowData['recipient_name']) || empty($rowData['state']) ||
                    empty($rowData['event_type']) || empty($rowData['event_title']) ||
                    empty($rowData['issue_date'])) {
                    $errors[] = 'Line '.($lineNumber + 2).': Missing required fields';

                    continue;
                }

                // Validate enum values
                if (! in_array($rowData['state'], ['attending', 'completing'])) {
                    $errors[] = 'Line '.($lineNumber + 2).": Invalid state value (must be 'attending' or 'completing')";

                    continue;
                }

                if (! in_array($rowData['event_type'], ['workshop', 'course'])) {
                    $errors[] = 'Line '.($lineNumber + 2).": Invalid event_type value (must be 'workshop' or 'course')";

                    continue;
                }

                // Dispatch job
                ProcessCertificateRow::dispatch(
                    $userId,
                    $rowData,
                    $userName,
                    $userOrgName,
                    $validated['certificate_template_id'],
                    $validated['email_template_id'],
                    $smtpProviderId
                );

                $processedCount++;
            } catch (\Exception $e) {
                $errors[] = 'Line '.($lineNumber + 2).': '.$e->getMessage();
            }
        }

        $message = "Successfully queued {$processedCount} certificates for processing.";
        if (count($errors) > 0) {
            $message .= ' '.count($errors).' rows had errors.';
        }

        return redirect()->route('dashboard.certificates.bulk')
            ->with('success', $message)
            ->with('errors', $errors);
    }
}
