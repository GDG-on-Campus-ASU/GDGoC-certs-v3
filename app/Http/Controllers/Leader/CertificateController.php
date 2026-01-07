<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of the user's certificates.
     */
    public function index()
    {
        $this->authorize('viewAny', Certificate::class);

        // ⚡ Bolt: Optimized query to select only necessary columns for the list view.
        // This avoids fetching large 'data' (JSON) and 'file_path' columns,
        // reducing memory usage and network overhead when listing many certificates.
        $certificates = auth()->user()->certificates()
            ->select([
                'id',
                'recipient_name',
                'recipient_email',
                'event_title',
                'event_type',
                'state',
                'status',
                'issue_date',
                'revoked_at',
                'revocation_reason',
                'unique_id', // Included for potential future links
                'created_at', // Included for latest() sorting consistency
            ])
            ->latest()
            ->paginate(20);

        return view('leader.certificates.index', compact('certificates'));
    }

    /**
     * Revoke the specified certificate.
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $this->authorize('revoke', $certificate);

        $request->validate([
            'revocation_reason' => 'required|string|max:255',
        ]);

        $certificate->status = 'revoked';
        $certificate->revoked_at = now();
        $certificate->revocation_reason = $request->revocation_reason;
        $certificate->save();

        return redirect()->back()->with('success', 'Certificate revoked successfully.');
    }
}
