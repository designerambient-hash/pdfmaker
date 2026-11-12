<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditorSaveController extends Controller
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
     * Save or autosave all modified pages and document metadata.
     */
    public function save(Request $request, string $documentId)
    {
        $document = $this->getAuthorizedDocument($documentId, ['pages']);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'pages' => 'required|array',
            'pages.*.id' => 'required|integer',
            'pages.*.canvas_data' => 'nullable|string',
            'pages.*.thumbnail' => 'nullable|string',
            'pages.*.width' => 'nullable|numeric',
            'pages.*.height' => 'nullable|numeric',
            'pages.*.background_color' => 'nullable|string',
            'document_thumbnail' => 'nullable|string',
            'create_version' => 'nullable|boolean',
            'version_name' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $document) {
            if ($request->filled('title')) {
                $document->title = $request->input('title');
            }

            if ($request->filled('document_thumbnail')) {
                $document->thumbnail = $request->input('document_thumbnail');
            }

            $document->touch();
            $document->save();

            // Update each page's canvas_data, thumbnail, dimensions
            foreach ($request->input('pages') as $pageData) {
                $page = DocumentPage::where('document_id', $document->id)
                    ->where('id', $pageData['id'])
                    ->first();

                if ($page) {
                    $updateFields = [];
                    if (isset($pageData['canvas_data'])) {
                        $updateFields['canvas_data'] = $pageData['canvas_data'];
                    }
                    if (isset($pageData['thumbnail'])) {
                        $updateFields['thumbnail'] = $pageData['thumbnail'];
                    }
                    if (isset($pageData['width'])) {
                        $updateFields['width'] = $pageData['width'];
                    }
                    if (isset($pageData['height'])) {
                        $updateFields['height'] = $pageData['height'];
                    }
                    if (isset($pageData['background_color'])) {
                        $updateFields['background_color'] = $pageData['background_color'];
                    }

                    if (!empty($updateFields)) {
                        $page->update($updateFields);
                    }
                }
            }

            // Version history snapshot if requested or milestone
            if ($request->boolean('create_version')) {
                $latestVersionNumber = $document->versions()->max('version_number') ?? 0;
                $newVersionNumber = $latestVersionNumber + 1;

                DocumentVersion::create([
                    'document_id' => $document->id,
                    'version_number' => $newVersionNumber,
                    'name' => $request->input('version_name') ?: "Version {$newVersionNumber}",
                    'document_snapshot' => [
                        'title' => $document->title,
                        'width' => $document->width,
                        'height' => $document->height,
                        'pages' => $document->pages()->get()->toArray(),
                    ],
                    'thumbnail' => $request->input('document_thumbnail'),
                ]);
            }

            return response()->json([
                'success' => true,
                'last_saved' => now()->toIso8601String(),
                'message' => 'Document saved successfully.',
            ]);
        });
    }

    /**
     * Restore document to a selected version snapshot.
     */
    public function restoreVersion(string $documentId, int $versionId)
    {
        $document = $this->getAuthorizedDocument($documentId);
        $version = DocumentVersion::where('document_id', $document->id)->findOrFail($versionId);

        $snapshot = $version->document_snapshot;

        return DB::transaction(function () use ($document, $snapshot) {
            if (isset($snapshot['title'])) {
                $document->title = $snapshot['title'];
            }
            $document->touch();
            $document->save();

            // Clear current pages and restore snapshot pages
            $document->pages()->delete();

            if (isset($snapshot['pages']) && is_array($snapshot['pages'])) {
                foreach ($snapshot['pages'] as $pageData) {
                    DocumentPage::create([
                        'document_id' => $document->id,
                        'page_number' => $pageData['page_number'] ?? 1,
                        'width' => $pageData['width'] ?? $document->width,
                        'height' => $pageData['height'] ?? $document->height,
                        'background_type' => $pageData['background_type'] ?? 'color',
                        'background_color' => $pageData['background_color'] ?? '#ffffff',
                        'background_gradient' => $pageData['background_gradient'] ?? null,
                        'canvas_data' => $pageData['canvas_data'] ?? json_encode(['version' => '5.3.0', 'objects' => []]),
                        'thumbnail' => $pageData['thumbnail'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Document restored to selected version.',
                'pages' => $document->pages()->orderBy('page_number')->get(),
            ]);
        });
    }

    /**
     * List version history for this document.
     */
    public function versions(string $documentId)
    {
        $document = $this->getAuthorizedDocument($documentId);
        $versions = $document->versions()->select(['id', 'version_number', 'name', 'thumbnail', 'created_at'])->get();

        return response()->json([
            'success' => true,
            'versions' => $versions,
        ]);
    }
}

