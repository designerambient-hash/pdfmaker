<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfUtilitiesController extends Controller
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * Show PDF Tools Hub page.
     */
    public function index()
    {
        return view('tools.index');
    }

    /**
     * Tool: Merge PDF files.
     */
    public function merge(Request $request)
    {
        $request->validate([
            'pdf_files' => 'required|array|min:2',
            'pdf_files.*' => 'required|file|mimes:pdf|max:30720',
        ]);

        try {
            $mergedUrl = $this->pdfService->mergePdfs($request->file('pdf_files'));

            return response()->json([
                'success' => true,
                'file_url' => asset($mergedUrl),
                'filename' => 'merged_document.pdf',
                'message' => 'PDFs merged successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error merging PDFs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tool: Split PDF into pages.
     */
    public function split(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:30720',
            'pages' => 'nullable|string', // e.g. "1,2,3" or "1-3"
        ]);

        try {
            $pagesList = null;
            if ($request->filled('pages')) {
                $raw = $request->input('pages');
                $pagesList = [];
                foreach (explode(',', $raw) as $part) {
                    if (str_contains($part, '-')) {
                        [$start, $end] = explode('-', $part, 2);
                        for ($i = (int)$start; $i <= (int)$end; $i++) {
                            $pagesList[] = $i;
                        }
                    } else {
                        $pagesList[] = (int) trim($part);
                    }
                }
            }

            $extracted = $this->pdfService->splitPdf($request->file('pdf_file'), $pagesList);

            $files = array_map(fn($path) => asset($path), $extracted);

            return response()->json([
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'message' => 'PDF split successfully into ' . count($files) . ' pages.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error splitting PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tool: Rotate PDF.
     */
    public function rotate(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:30720',
            'angle' => 'nullable|in:90,180,270',
        ]);

        try {
            $angle = (int) $request->input('angle', 90);
            $rotatedUrl = $this->pdfService->rotatePdf($request->file('pdf_file'), $angle);

            return response()->json([
                'success' => true,
                'file_url' => asset($rotatedUrl),
                'filename' => 'rotated_document.pdf',
                'message' => "PDF rotated by {$angle} degrees successfully!",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rotating PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tool: Add Watermark.
     */
    public function watermark(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:30720',
            'watermark_text' => 'required|string|max:100',
            'position' => 'nullable|in:center,top,bottom',
        ]);

        try {
            $text = $request->input('watermark_text');
            $pos = $request->input('position', 'center');
            $url = $this->pdfService->addWatermark($request->file('pdf_file'), $text, $pos);

            return response()->json([
                'success' => true,
                'file_url' => asset($url),
                'filename' => 'watermarked_document.pdf',
                'message' => 'Watermark added successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding watermark: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tool: Add Page Numbers.
     */
    public function pageNumbers(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:30720',
            'format' => 'nullable|string',
            'position' => 'nullable|in:bottom-center,bottom-right,top-center,top-right',
        ]);

        try {
            $format = $request->input('format', 'Page {page} of {total}');
            $pos = $request->input('position', 'bottom-center');
            $url = $this->pdfService->addPageNumbers($request->file('pdf_file'), $format, $pos);

            return response()->json([
                'success' => true,
                'file_url' => asset($url),
                'filename' => 'numbered_document.pdf',
                'message' => 'Page numbers added successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding page numbers: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tool: Images to PDF.
     */
    public function imagesToPdf(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|mimes:jpeg,jpg,png,webp|max:20480',
        ]);

        try {
            $url = $this->pdfService->imagesToPdf($request->file('images'));

            return response()->json([
                'success' => true,
                'file_url' => asset($url),
                'filename' => 'converted_images.pdf',
                'message' => 'Images converted to PDF successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error converting images to PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}

