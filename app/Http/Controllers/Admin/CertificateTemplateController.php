<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Services\TemplatePreviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateTemplateController extends Controller
{
    /**
     * Preview the certificate template.
     */
    public function preview(Request $request, TemplatePreviewService $previewService)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
        ]);

        $content = $previewService->applyReplacements($validated['content']);

        return response()->json([
            'content' => $content,
            'type' => $validated['type'],
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = CertificateTemplate::with('user')
            ->select('id', 'name', 'type', 'is_global', 'user_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.templates.certificates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.templates.certificates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
            'is_global' => ['boolean'],
        ]);

        CertificateTemplate::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CertificateTemplate $certificateTemplate)
    {
        return view('admin.templates.certificates.edit', compact('certificateTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', Rule::in(['svg', 'blade'])],
            'is_global' => ['boolean'],
        ]);

        $certificateTemplate->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'type' => $validated['type'],
            'is_global' => $request->boolean('is_global'),
        ]);

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CertificateTemplate $certificateTemplate)
    {
        $certificateTemplate->delete();

        return redirect()->route('admin.templates.certificates.index')
            ->with('success', 'Certificate template deleted successfully.');
    }
}
