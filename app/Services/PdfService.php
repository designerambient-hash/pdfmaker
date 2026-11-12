<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfService
{
    /**
     * Merge multiple uploaded PDF files into a single output PDF file.
     *
     * @param array<UploadedFile|string> $files
     * @return string Path to generated merged PDF in public storage
     */
    public function mergePdfs(array $files): string
    {
        $pdf = new Fpdi();

        foreach ($files as $file) {
            $filePath = is_string($file) ? $file : $file->getRealPath();
            $pageCount = $pdf->setSourceFile($filePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        $filename = 'merged_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/public/exports/' . $filename);
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return 'storage/exports/' . $filename;
    }

    /**
     * Split a PDF into individual pages or specific page range.
     *
     * @param string|UploadedFile $file
     * @param array<int>|null $pagesToExtract
     * @return array<string> Array of paths to generated page PDFs
     */
    public function splitPdf($file, ?array $pagesToExtract = null): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $sourcePdf = new Fpdi();
        $pageCount = $sourcePdf->setSourceFile($filePath);

        $extractedFiles = [];
        $exportDir = storage_path('app/public/exports');
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        $targetPages = $pagesToExtract ?? range(1, $pageCount);

        foreach ($targetPages as $pageNo) {
            if ($pageNo < 1 || $pageNo > $pageCount) {
                continue;
            }

            $pdf = new Fpdi();
            $pdf->setSourceFile($filePath);
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $filename = 'split_page_' . $pageNo . '_' . uniqid() . '.pdf';
            $outputPath = $exportDir . '/' . $filename;
            $pdf->Output('F', $outputPath);

            $extractedFiles[] = 'storage/exports/' . $filename;
        }

        return $extractedFiles;
    }

    /**
     * Rotate pages of a PDF by 90, 180, or 270 degrees.
     */
    public function rotatePdf($file, int $angle = 90): string
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $isLandscape = ($size['width'] > $size['height']);
            // When rotating 90 or 270, switch orientation
            if (in_array($angle, [90, 270])) {
                $orientation = $isLandscape ? 'P' : 'L';
                $pdf->AddPage($orientation, [$size['height'], $size['width']]);
            } else {
                $orientation = $isLandscape ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            }

            $pdf->useTemplate($templateId);
        }

        $filename = 'rotated_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/public/exports/' . $filename);
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return 'storage/exports/' . $filename;
    }

    /**
     * Add Watermark text to all pages of a PDF.
     */
    public function addWatermark($file, string $watermarkText, string $position = 'center'): string
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Add watermark text
            $pdf->SetFont('Helvetica', 'B', 36);
            $pdf->SetTextColor(180, 180, 180);

            if ($position === 'center') {
                $x = max(10, ($size['width'] / 2) - 60);
                $y = $size['height'] / 2;
            } elseif ($position === 'top') {
                $x = 20;
                $y = 30;
            } else {
                $x = 20;
                $y = $size['height'] - 30;
            }

            $pdf->Text($x, $y, $watermarkText);
        }

        $filename = 'watermarked_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/public/exports/' . $filename);
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return 'storage/exports/' . $filename;
    }

    /**
     * Add page numbering to a PDF document.
     */
    public function addPageNumbers($file, string $format = 'Page {page} of {total}', string $position = 'bottom-center'): string
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            $text = str_replace(['{page}', '{total}'], [$pageNo, $pageCount], $format);

            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(100, 100, 100);

            $y = ($position === 'top-center' || $position === 'top-right') ? 15 : ($size['height'] - 12);
            $x = ($position === 'bottom-right' || $position === 'top-right') ? ($size['width'] - 40) : ($size['width'] / 2 - 20);

            $pdf->Text($x, $y, $text);
        }

        $filename = 'numbered_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/public/exports/' . $filename);
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return 'storage/exports/' . $filename;
    }

    /**
     * Convert images (JPG, PNG, WEBP) to a PDF document.
     *
     * @param array<UploadedFile> $images
     */
    public function imagesToPdf(array $images): string
    {
        $pdf = new Fpdi();

        foreach ($images as $img) {
            $imgPath = is_string($img) ? $img : $img->getRealPath();
            $imgInfo = getimagesize($imgPath);

            if (!$imgInfo) {
                continue;
            }

            $imgWidth = $imgInfo[0];
            $imgHeight = $imgInfo[1];

            // Convert px to mm (approx 72 dpi or 96 dpi)
            $mmWidth = $imgWidth * 0.264583;
            $mmHeight = $imgHeight * 0.264583;

            $orientation = ($mmWidth > $mmHeight) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$mmWidth, $mmHeight]);

            // If image is webp, convert temporary png using GD
            $finalPath = $imgPath;
            $tempFile = null;
            if ($imgInfo['mime'] === 'image/webp') {
                $im = imagecreatefromwebp($imgPath);
                if ($im) {
                    $tempFile = tempnam(sys_get_temp_dir(), 'img_') . '.png';
                    imagepng($im, $tempFile);
                    imagedestroy($im);
                    $finalPath = $tempFile;
                }
            }

            $pdf->Image($finalPath, 0, 0, $mmWidth, $mmHeight);

            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        $filename = 'converted_images_' . uniqid() . '.pdf';
        $outputPath = storage_path('app/public/exports/' . $filename);
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $pdf->Output('F', $outputPath);

        return 'storage/exports/' . $filename;
    }

    /**
     * Inspect PDF info (page count, page dimensions).
     */
    public function getPdfInfo($file): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);
        $pages = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($templateId);
            $pages[] = [
                'page' => $i,
                'width' => round($size['width'] * 3.7795275591, 2), // mm to px approx
                'height' => round($size['height'] * 3.7795275591, 2),
                'orientation' => $size['width'] > $size['height'] ? 'landscape' : 'portrait',
            ];
        }

        return [
            'page_count' => $pageCount,
            'pages' => $pages,
        ];
    }
}

