<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;

class ConfigurationController extends Controller
{
    /**
     * Display the configuration page with all settings.
     */
    public function index()
    {
        // Get user's SMTP providers
        $smtpProviders = auth()->user()->smtpProviders()->orderBy('created_at', 'desc')->get();

        // Get user's email templates
        // Optimization: Select only necessary columns to avoid hydrating heavy 'body' field
        $userEmailTemplates = auth()->user()->emailTemplates()
            ->select('id', 'name', 'subject', 'created_at', 'original_template_id', 'user_id')
            ->get();

        $globalEmailTemplates = EmailTemplate::where('is_global', true)
            ->select('id', 'name', 'subject', 'is_global')
            ->get();

        // Get user's certificate templates
        // Optimization: Select only necessary columns to avoid hydrating heavy 'content' field
        $userCertificateTemplates = auth()->user()->certificateTemplates()
            ->select('id', 'name', 'type', 'created_at', 'original_template_id', 'user_id')
            ->get();

        $globalCertificateTemplates = CertificateTemplate::where('is_global', true)
            ->select('id', 'name', 'type', 'is_global')
            ->get();

        return view('dashboard.configuration.index', compact(
            'smtpProviders',
            'userEmailTemplates',
            'globalEmailTemplates',
            'userCertificateTemplates',
            'globalCertificateTemplates'
        ));
    }
}
