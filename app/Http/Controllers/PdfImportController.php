<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAsset;
use App\Models\DocumentPage;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PdfImportController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Upload an existing PDF and create an editable project document.
     */
    public function import(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:51200', // up to 50MB
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('pdf_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $title = $request->input('title') ?: $originalName;
        $safeName = Str::random(24) . '.pdf';

        $storedPath = $file->storeAs('uploads/pdfs', $safeName, 'public');
        $fullPath = storage_path('app/public/' . $storedPath);
        $publicUrl = asset('storage/' . $storedPath);

        // Inspect PDF info
        $pdfInfo = $this->pdfService->getPdfInfo($fullPath);
        $pageCount = $pdfInfo['page_count'] ?? 1;
        $firstPage = $pdfInfo['pages'][0] ?? ['width' => 794, 'height' => 1123, 'orientation' => 'portrait'];

        $document = Document::create([
            'id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'title' => $title,
            'page_size' => 'Custom',
            'unit' => 'px',
            'width' => $firstPage['width'],
            'height' => $firstPage['height'],
            'orientation' => $firstPage['orientation'],
            'status' => 'active',
            'metadata' => [
                'imported_pdf_url' => $publicUrl,
                'imported_pdf_path' => $fullPath,
                'page_count' => $pageCount,
                'is_imported' => true,
            ],
        ]);

        // Create an asset record
        DocumentAsset::create([
            'user_id' => Auth::id(),
            'document_id' => $document->id,
            'session_id' => $request->session()->getId(),
            'name' => $file->getClientOriginalName(),
            'file_path' => $publicUrl,
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => $file->getSize(),
        ]);

        // Create each page in the document
        for ($i = 1; $i <= $pageCount; $i++) {
            $pageMeta = $pdfInfo['pages'][$i - 1] ?? $firstPage;
            DocumentPage::create([
                'document_id' => $document->id,
                'page_number' => $i,
                'width' => $pageMeta['width'],
                'height' => $pageMeta['height'],
                'background_type' => 'pdf_page',
                'background_image' => $publicUrl,
                'margins' => ['pdf_page_number' => $i],
                'canvas_data' => json_encode(['version' => '5.3.0', 'objects' => []]),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'document' => $document,
                'redirect' => route('documents.edit', $document->id),
            ]);
        }

        return redirect()->route('documents.edit', $document->id);
    }
}

