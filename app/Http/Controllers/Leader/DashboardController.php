<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;

class DashboardController extends Controller
{
    /**
     * Display the leader dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get statistics for the authenticated leader
        $stats = [
            'total_certificates' => Certificate::where('user_id', $user->id)->count(),
            'active_certificates' => Certificate::where('user_id', $user->id)
                ->where('status', 'issued')->count(),
            'emails_sent' => Certificate::where('user_id', $user->id)
                ->whereNotNull('recipient_email')->count(),
            'certificate_templates' => CertificateTemplate::where('user_id', $user->id)
                ->orWhere('is_global', true)->count(),
            'email_templates' => EmailTemplate::where('user_id', $user->id)
                ->orWhere('is_global', true)->count(),
            'revoked_certificates' => Certificate::where('user_id', $user->id)
                ->where('status', 'revoked')->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}
