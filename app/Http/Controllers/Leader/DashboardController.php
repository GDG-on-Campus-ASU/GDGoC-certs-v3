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
        
        // Optimize certificate statistics by aggregating in a single query
        $certificateStats = Certificate::where('user_id', $user->id)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'issued' then 1 end) as active")
            ->selectRaw("count(case when recipient_email is not null then 1 end) as emails_sent")
            ->selectRaw("count(case when status = 'revoked' then 1 end) as revoked")
            ->first();

        // Get statistics for the authenticated leader
        $stats = [
            'total_certificates' => $certificateStats->total ?? 0,
            'active_certificates' => $certificateStats->active ?? 0,
            'emails_sent' => $certificateStats->emails_sent ?? 0,
            'certificate_templates' => CertificateTemplate::where('user_id', $user->id)
                ->orWhere('is_global', true)->count(),
            'email_templates' => EmailTemplate::where('user_id', $user->id)
                ->orWhere('is_global', true)->count(),
            'revoked_certificates' => $certificateStats->revoked ?? 0,
        ];

        return view('dashboard', compact('stats'));
    }
}
