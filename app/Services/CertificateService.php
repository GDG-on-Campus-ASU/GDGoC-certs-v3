<?php

namespace App\Services;

use App\Exceptions\CertificateTemplateNotFoundException;
use App\Models\Certificate;
use Illuminate\Support\Facades\App;

class CertificateService
{
    /**
     * Generate a PDF certificate from a template and certificate data.
     */
    public function generate(Certificate $certificate): string
    {
        // Get the template
        $template = $certificate->certificateTemplate;

        if (! $template) {
            throw new CertificateTemplateNotFoundException;
        }

        // Get the template content
        $content = $template->content;

        // Create an array of key-value pairs for replacement
        $replacements = [
            '{Recipient_Name}' => $certificate->recipient_name,
            '{Event_Title}' => $certificate->event_title,
            '{Org_Name}' => $certificate->org_name,
            '{state}' => $certificate->state,
            '{event_type}' => $certificate->event_type,
            '{issue_date}' => $certificate->issue_date->toFormattedDateString(),
            '{issuer_name}' => $certificate->issuer_name,
            '{unique_id}' => $certificate->unique_id,
        ];

        // Sanitize replacements to prevent XSS/PDF Injection
        $sanitizedReplacements = array_map(function ($value) {
            return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        }, $replacements);

        // Replace variables in content
        $html = str_replace(array_keys($sanitizedReplacements), array_values($sanitizedReplacements), $content);

        // Generate the PDF
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'landscape');

        // Return the PDF binary
        return $pdf->output();
    }

    /**
     * Generate and store the certificate PDF.
     */
    public function store(Certificate $certificate): string
    {
        $pdfContent = $this->generate($certificate);
        $filename = 'certificates/' . $certificate->unique_id . '.pdf';
        
        \Illuminate\Support\Facades\Storage::put($filename, $pdfContent);
        
        return $filename;
    }
}
