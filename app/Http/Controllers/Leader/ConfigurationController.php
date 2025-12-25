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
        $userEmailTemplates = auth()->user()->emailTemplates;
        $globalEmailTemplates = EmailTemplate::where('is_global', true)->get();

        // Get user's certificate templates
        $userCertificateTemplates = auth()->user()->certificateTemplates;
        $globalCertificateTemplates = CertificateTemplate::where('is_global', true)->get();

        return view('dashboard.configuration.index', compact(
            'smtpProviders',
            'userEmailTemplates',
            'globalEmailTemplates',
            'userCertificateTemplates',
            'globalCertificateTemplates'
        ));
    }
}
