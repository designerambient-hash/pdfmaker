<?php

namespace App\Http\Controllers;

use App\Models\CustomFont;
use App\Models\Document;
use App\Models\DocumentAsset;
use App\Models\DocumentPage;
use App\Models\SavedColor;
use App\Models\Signature;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Standard page presets mapping (dimensions in pixels at 96 DPI).
     */
    public static array $presets = [
        'A4' => ['width' => 794, 'height' => 1123],
        'A3' => ['width' => 1123, 'height' => 1587],
        'A5' => ['width' => 559, 'height' => 794],
        'Letter' => ['width' => 816, 'height' => 1056],
        'Legal' => ['width' => 816, 'height' => 1344],
        'Tabloid' => ['width' => 1056, 'height' => 1632],
        'Business Card' => ['width' => 336, 'height' => 192],
    ];

    /**
     * Create a new document with an initial page.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'page_size' => 'nullable|string',
            'orientation' => 'nullable|in:portrait,landscape',
            'unit' => 'nullable|in:px,mm',
            'custom_width' => 'nullable|numeric|min:10',
            'custom_height' => 'nullable|numeric|min:10',
        ]);

        $title = $request->input('title', 'Untitled Document') ?: 'Untitled Document';
        $pageSize = $request->input('page_size', 'A4');
        $orientation = $request->input('orientation', 'portrait');
        $unit = $request->input('unit', 'px');

        if ($pageSize === 'Custom') {
            $width = (float) $request->input('custom_width', 794);
            $height = (float) $request->input('custom_height', 1123);
            if ($unit === 'mm') {
                $width = round($width * 3.7795275591, 1);
                $height = round($height * 3.7795275591, 1);
            }
        } elseif (isset(self::$presets[$pageSize])) {
            $preset = self::$presets[$pageSize];
            $width = $preset['width'];
            $height = $preset['height'];
        } else {
            $width = 794;
            $height = 1123;
        }

        // Adjust dimensions based on orientation
        if ($orientation === 'landscape' && $width < $height) {
            $temp = $width;
            $width = $height;
            $height = $temp;
        } elseif ($orientation === 'portrait' && $width > $height) {
            $temp = $width;
            $width = $height;
            $height = $temp;
        }

        $document = Document::create([
            'id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'title' => $title,
            'page_size' => $pageSize,
            'unit' => $unit,
            'width' => $width,
            'height' => $height,
            'orientation' => $orientation,
            'status' => 'active',
            'settings' => [
                'grid' => false,
                'rulers' => true,
                'snapping' => true,
                'zoom' => 1.0,
            ],
        ]);

        // Create the initial first page
        DocumentPage::create([
            'document_id' => $document->id,
            'page_number' => 1,
            'width' => $width,
            'height' => $height,
            'background_type' => 'color',
            'background_color' => '#ffffff',
            'canvas_data' => json_encode(['version' => '5.3.0', 'objects' => []]),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'document' => $document,
                'redirect' => route('documents.edit', $document->id),
            ]);
        }

        return redirect()->route('documents.edit', $document->id);
    }

    /**
     * Get document strictly authorized for the current logged-in user.
     */
    protected function getAuthorizedDocument(string $id, array $with = []): Document
    {
        $query = Document::query();
        if (!empty($with)) {
            $query->with($with);
        }

        return $query->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Open the full-featured PDF Editor interface for a document.
     */
    public function edit(Request $request, string $id)
    {
        $document = $this->getAuthorizedDocument($id, ['pages', 'assets']);

        $userId = Auth::id();

        $savedColors = SavedColor::where('user_id', $userId)->get();
        $customFonts = CustomFont::where('user_id', $userId)->get();
        $signatures = Signature::where('user_id', $userId)->get();

        $templates = Template::where('is_system', true)
            ->orWhere('user_id', $userId)
            ->get();

        $userAssets = DocumentAsset::where(function ($q) use ($userId, $document) {
            $q->where('user_id', $userId)
              ->orWhere('document_id', $document->id);
        })->latest()->get();

        return view('editor.index', compact(
            'document',
            'savedColors',
            'customFonts',
            'signatures',
            'templates',
            'userAssets'
        ));
    }

    /**
     * Show document details (JSON or preview).
     */
    public function show(string $id)
    {
        $document = $this->getAuthorizedDocument($id, ['pages', 'assets']);

        return response()->json([
            'success' => true,
            'document' => $document,
        ]);
    }

    /**
     * Update document metadata, title, page dimensions, or settings.
     */
    public function update(Request $request, string $id)
    {
        $document = $this->getAuthorizedDocument($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'page_size' => 'sometimes|string',
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'orientation' => 'sometimes|in:portrait,landscape',
            'settings' => 'sometimes|array',
            'thumbnail' => 'nullable|string',
        ]);

        $document->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully.',
            'document' => $document,
        ]);
    }

    /**
     * Duplicate a document and all its pages completely.
     */
    public function duplicate(string $id)
    {
        $document = $this->getAuthorizedDocument($id, ['pages']);
        $cloned = $document->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Document duplicated successfully.',
            'document' => $cloned,
            'redirect' => route('documents.edit', $cloned->id),
        ]);
    }

    /**
     * Toggle Starred flag.
     */
    public function toggleStar(string $id)
    {
        $document = $this->getAuthorizedDocument($id);
        $document->is_starred = !$document->is_starred;
        $document->save();

        return response()->json([
            'success' => true,
            'is_starred' => $document->is_starred,
        ]);
    }

    /**
     * Move document to trash.
     */
    public function destroy(string $id)
    {
        $document = $this->getAuthorizedDocument($id);
        $document->status = 'trash';
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Document moved to trash.',
        ]);
    }

    /**
     * Restore document from trash.
     */
    public function restore(string $id)
    {
        $document = $this->getAuthorizedDocument($id);
        $document->status = 'active';
        $document->save();

        return response()->json([
            'success' => true,
            'message' => 'Document restored successfully.',
        ]);
    }

    /**
     * Permanently delete document.
     */
    public function forceDelete(string $id)
    {
        $document = $this->getAuthorizedDocument($id);
        $document->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Document permanently deleted.',
        ]);
    }

    /**
     * Perform bulk action on selected documents (trash, restore, forceDelete, star, unstar).
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:trash,restore,forceDelete,star,unstar',
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        $userId = Auth::id();

        $query = Document::whereIn('id', $ids)->where('user_id', $userId);
        $count = $query->count();

        switch ($action) {
            case 'trash':
                $query->update(['status' => 'trash']);
                $msg = "{$count} document(s) moved to trash.";
                break;
            case 'restore':
                $query->update(['status' => 'active']);
                $msg = "{$count} document(s) restored.";
                break;
            case 'forceDelete':
                $query->forceDelete();
                $msg = "{$count} document(s) permanently deleted.";
                break;
            case 'star':
                $query->update(['is_starred' => true]);
                $msg = "{$count} document(s) starred.";
                break;
            case 'unstar':
                $query->update(['is_starred' => false]);
                $msg = "{$count} document(s) unstarred.";
                break;
            default:
                $msg = "Action completed.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'count' => $count,
        ]);
    }

    /**
     * Empty all trashed documents permanently.
     */
    public function emptyTrash(Request $request)
    {
        $userId = Auth::id();

        $query = Document::where('status', 'trash')->where('user_id', $userId);
        $count = $query->count();
        $query->forceDelete();

        return response()->json([
            'success' => true,
            'message' => "Trash emptied. {$count} document(s) permanently removed.",
            'count' => $count,
        ]);
    }
}

