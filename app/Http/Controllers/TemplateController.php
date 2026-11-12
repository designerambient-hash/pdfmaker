<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * List all templates (system and user custom), with category and search filter.
     */
    public function index(Request $request)
    {
        $category = $request->query('category');
        $search = $request->query('search');

        $query = Template::query();

        // System templates + Authenticated user's custom templates
        $userId = Auth::id();
        $query->where(function ($q) use ($userId) {
            $q->where('is_system', true);
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->get();

        $categories = Template::select('category')->distinct()->pluck('category');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'templates' => $templates,
                'categories' => $categories,
            ]);
        }

        return view('templates.index', compact('templates', 'categories'));
    }

    /**
     * Create a new document initialized from a chosen template.
     */
    public function useTemplate(Request $request, int $id)
    {
        $template = Template::findOrFail($id);
        $data = $template->document_data;

        $document = Document::create([
            'id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'title' => $template->title . ' (From Template)',
            'page_size' => $template->page_size,
            'unit' => 'px',
            'width' => $template->width,
            'height' => $template->height,
            'orientation' => $template->orientation,
            'status' => 'active',
            'thumbnail' => $template->thumbnail,
        ]);

        if (isset($data['pages']) && is_array($data['pages'])) {
            foreach ($data['pages'] as $index => $pageData) {
                DocumentPage::create([
                    'document_id' => $document->id,
                    'page_number' => $index + 1,
                    'width' => $pageData['width'] ?? $template->width,
                    'height' => $pageData['height'] ?? $template->height,
                    'background_color' => $pageData['background_color'] ?? '#ffffff',
                    'canvas_data' => is_string($pageData['canvas_data'] ?? null)
                        ? $pageData['canvas_data']
                        : json_encode($pageData['canvas_data'] ?? ['version' => '5.3.0', 'objects' => []]),
                ]);
            }
        } else {
            DocumentPage::create([
                'document_id' => $document->id,
                'page_number' => 1,
                'width' => $template->width,
                'height' => $template->height,
                'background_color' => '#ffffff',
                'canvas_data' => json_encode(['version' => '5.3.0', 'objects' => []]),
            ]);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('documents.edit', $document->id),
        ]);
    }

    /**
     * Save the current document or page as a new custom reusable template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'document_id' => 'required|string',
            'thumbnail' => 'nullable|string',
        ]);

        $document = Document::with('pages')
            ->where('id', $validated['document_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $pagesData = [];
        foreach ($document->pages as $page) {
            $pagesData[] = [
                'width' => $page->width,
                'height' => $page->height,
                'background_color' => $page->background_color,
                'canvas_data' => $page->canvas_data,
            ];
        }

        $template = Template::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'page_size' => $document->page_size,
            'width' => $document->width,
            'height' => $document->height,
            'orientation' => $document->orientation,
            'thumbnail' => $validated['thumbnail'] ?? $document->thumbnail,
            'document_data' => [
                'pages' => $pagesData,
            ],
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully.',
            'template' => $template,
        ]);
    }

    /**
     * Delete custom template.
     */
    public function destroy(int $id)
    {
        $template = Template::where('is_system', false)
            ->where('user_id', Auth::id())
            ->findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted.',
        ]);
    }
}

