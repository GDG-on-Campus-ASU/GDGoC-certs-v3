<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class PublicCertificateController extends Controller
{
    /**
     * Display the validation form.
     */
    public function index()
    {
        return view('validate.index');
    }

    /**
     * Validate and redirect to certificate show page.
     */
    public function validate(Request $request)
    {
        $request->validate([
            'unique_id' => 'required|string',
        ]);

        return redirect()->route('public.certificate.show', ['unique_id' => $request->unique_id]);
    }

    /**
     * Display the specified certificate.
     */
    public function show($unique_id)
    {
        $certificate = Certificate::where('unique_id', $unique_id)->firstOrFail();

        return view('validate.show', compact('certificate'));
    }

    /**
     * Download the certificate PDF.
     */
    public function download(CertificateService $certificateService, $unique_id)
    {
        $certificate = Certificate::where('unique_id', $unique_id)
            ->where('status', 'issued')
            ->firstOrFail();

        if ($certificate->file_path && \Illuminate\Support\Facades\Storage::exists($certificate->file_path)) {
            $content = \Illuminate\Support\Facades\Storage::get($certificate->file_path);
            return response($content)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="certificate.pdf"');
        }

        $pdfData = $certificateService->generate($certificate);

        // Optimization: Cache the generated PDF for future requests
        $filename = 'certificates/' . $certificate->unique_id . '.pdf';
        \Illuminate\Support\Facades\Storage::put($filename, $pdfData);
        $certificate->update(['file_path' => $filename]);

        return response($pdfData)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="certificate.pdf"');
    }
}
