<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentPageController extends Controller
{
    /**
     * Get document strictly authorized for the current user.
     */
    protected function getAuthorizedDocument(string $documentId, array $with = []): Document
    {
        $query = Document::query();
        if (!empty($with)) {
            $query->with($with);
        }

        return $query->where('id', $documentId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Add a new blank page to the document.
     */
    public function store(Request $request, string $documentId)
    {
        $document = $this->getAuthorizedDocument($documentId, ['pages']);

        $request->validate([
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'background_color' => 'nullable|string',
            'insert_after' => 'nullable|integer',
        ]);

        $width = $request->input('width', $document->width);
        $height = $request->input('height', $document->height);
        $bgColor = $request->input('background_color', '#ffffff');
        $insertAfter = $request->input('insert_after');

        return DB::transaction(function () use ($document, $width, $height, $bgColor, $insertAfter) {
            $currentPages = $document->pages()->orderBy('page_number')->get();

            if ($insertAfter !== null && $insertAfter >= 1 && $insertAfter < $currentPages->count()) {
                // Shift subsequent pages up
                $document->pages()
                    ->where('page_number', '>', $insertAfter)
                    ->increment('page_number');
                $newPageNumber = $insertAfter + 1;
            } else {
                $newPageNumber = $currentPages->count() + 1;
            }

            $page = DocumentPage::create([
                'document_id' => $document->id,
                'page_number' => $newPageNumber,
                'width' => $width,
                'height' => $height,
                'background_type' => 'color',
                'background_color' => $bgColor,
                'canvas_data' => json_encode(['version' => '5.3.0', 'objects' => []]),
            ]);

            return response()->json([
                'success' => true,
                'page' => $page,
                'pages' => $document->pages()->orderBy('page_number')->get(),
            ]);
        });
    }

    /**
     * Duplicate an existing page with its canvas data and styling.
     */
    public function duplicate(string $documentId, int $pageId)
    {
        $document = $this->getAuthorizedDocument($documentId);
        $sourcePage = DocumentPage::where('document_id', $document->id)->findOrFail($pageId);

        return DB::transaction(function () use ($document, $sourcePage) {
            $insertPosition = $sourcePage->page_number + 1;

            $document->pages()
                ->where('page_number', '>=', $insertPosition)
                ->increment('page_number');

            $newPage = $sourcePage->replicate();
            $newPage->page_number = $insertPosition;
            $newPage->created_at = now();
            $newPage->updated_at = now();
            $newPage->save();

            return response()->json([
                'success' => true,
                'page' => $newPage,
                'pages' => $document->pages()->orderBy('page_number')->get(),
            ]);
        });
    }

    /**
     * Update page background, borders, margins, or dimensions.
     */
    public function update(Request $request, string $documentId, int $pageId)
    {
        $document = $this->getAuthorizedDocument($documentId);
        $page = DocumentPage::where('document_id', $document->id)->findOrFail($pageId);

        $validated = $request->validate([
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'background_type' => 'sometimes|string',
            'background_color' => 'sometimes|string',
            'background_gradient' => 'nullable|array',
            'background_image' => 'nullable|string',
            'background_opacity' => 'sometimes|numeric',
            'border_color' => 'nullable|string',
            'border_width' => 'sometimes|numeric',
            'border_style' => 'sometimes|string',
            'margins' => 'nullable|array',
            'canvas_data' => 'nullable|string',
            'thumbnail' => 'nullable|string',
        ]);

        $page->update($validated);

        return response()->json([
            'success' => true,
            'page' => $page,
        ]);
    }

    /**
     * Reorder pages in the document.
     */
    public function reorder(Request $request, string $documentId)
    {
        $document = $this->getAuthorizedDocument($documentId);
        $request->validate([
            'order' => 'required|array', // array of page IDs in target order
            'order.*' => 'integer',
        ]);

        $order = $request->input('order');

        DB::transaction(function () use ($document, $order) {
            foreach ($order as $index => $pageId) {
                DocumentPage::where('document_id', $document->id)
                    ->where('id', $pageId)
                    ->update(['page_number' => $index + 1]);
            }
        });

        return response()->json([
            'success' => true,
            'pages' => $document->pages()->orderBy('page_number')->get(),
        ]);
    }

    /**
     * Delete a page from the document (guaranteeing at least 1 page remains).
     */
    public function destroy(string $documentId, int $pageId)
    {
        $document = $this->getAuthorizedDocument($documentId, ['pages']);

        if ($document->pages->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Document must contain at least one page.',
            ], 422);
        }

        $page = DocumentPage::where('document_id', $document->id)->findOrFail($pageId);

        DB::transaction(function () use ($document, $page) {
            $deletedPageNumber = $page->page_number;
            $page->delete();

            // Re-index remaining pages
            $remaining = $document->pages()->where('page_number', '>', $deletedPageNumber)->get();
            foreach ($remaining as $p) {
                $p->decrement('page_number');
            }
        });

        return response()->json([
            'success' => true,
            'pages' => $document->pages()->orderBy('page_number')->get(),
        ]);
    }
}

