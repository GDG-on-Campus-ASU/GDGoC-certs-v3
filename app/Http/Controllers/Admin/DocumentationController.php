<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentationPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = DocumentationPage::orderBy('order')->get();

        return view('admin.documentation.index', compact('pages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.documentation.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['title']);

        // Check if slug exists and find the highest counter if needed
        $existingSlugs = DocumentationPage::where('slug', 'like', $slug.'%')
            ->pluck('slug')
            ->toArray();

        if (in_array($slug, $existingSlugs)) {
            // Find the highest counter value
            $maxCounter = 0;
            foreach ($existingSlugs as $existingSlug) {
                if (preg_match('/^'.preg_quote($slug, '/').'-(\d+)$/', $existingSlug, $matches)) {
                    $maxCounter = max($maxCounter, (int) $matches[1]);
                }
            }
            $slug = $slug.'-'.($maxCounter + 1);
        }

        $validated['slug'] = $slug;

        DocumentationPage::create($validated);

        return redirect()->route('admin.documentation.index')
            ->with('success', 'Documentation page created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentationPage $documentation)
    {
        return redirect()->route('admin.documentation.edit', $documentation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentationPage $documentation)
    {
        return view('admin.documentation.edit', compact('documentation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentationPage $documentation)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $slug = Str::slug($validated['title']);

        // Check if slug exists and find the highest counter if needed (excluding current page)
        $existingSlugs = DocumentationPage::where('id', '!=', $documentation->id)
            ->where('slug', 'like', $slug.'%')
            ->pluck('slug')
            ->toArray();

        if (in_array($slug, $existingSlugs)) {
            // Find the highest counter value
            $maxCounter = 0;
            foreach ($existingSlugs as $existingSlug) {
                if (preg_match('/^'.preg_quote($slug, '/').'-(\d+)$/', $existingSlug, $matches)) {
                    $maxCounter = max($maxCounter, (int) $matches[1]);
                }
            }
            $slug = $slug.'-'.($maxCounter + 1);
        }

        $validated['slug'] = $slug;

        $documentation->update($validated);

        return redirect()->route('admin.documentation.index')
            ->with('success', 'Documentation page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentationPage $documentation)
    {
        $documentation->delete();

        return redirect()->route('admin.documentation.index')
            ->with('success', 'Documentation page deleted successfully.');
    }
}
