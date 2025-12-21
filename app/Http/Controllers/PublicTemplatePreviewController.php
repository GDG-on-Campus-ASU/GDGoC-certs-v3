<?php

namespace App\Http\Controllers;

use App\Services\TemplatePreviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicTemplatePreviewController extends Controller
{
    protected $previewService;

    public function __construct(TemplatePreviewService $previewService)
    {
        $this->previewService = $previewService;
    }

    /**
     * Preview the certificate template.
     */
    public function previewCertificate(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
        ]);

        $content = $this->previewService->applyReplacements($validated['content']);

        return response()->json([
            'content' => $content,
            'type' => $validated['type'],
        ]);
    }

    /**
     * Preview the email template.
     */
    public function previewEmail(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        $subject = $this->previewService->applyReplacements($validated['subject']);
        $body = $this->previewService->applyReplacements($validated['body']);

        return response()->json([
            'subject' => $subject,
            'body' => $body,
        ]);
    }
}
