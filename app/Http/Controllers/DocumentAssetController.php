<?php

namespace App\Http\Controllers;

use App\Models\CustomFont;
use App\Models\DocumentAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentAssetController extends Controller
{
    /**
     * Upload an image or vector asset for use in the canvas editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,jpg,png,webp,svg,gif|max:15360', // up to 15MB
            'document_id' => 'nullable|string',
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $safeName = Str::random(24) . '.' . $extension;

        // SVG Sanitization
        if ($extension === 'svg' || str_contains($mime, 'svg')) {
            $content = file_get_contents($file->getRealPath());
            // Strip scripts, event handlers, and data uris with javascript
            $sanitized = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
            $sanitized = preg_replace('/on\w+="[^"]*"/i', '', $sanitized);
            $sanitized = preg_replace('/on\w+=\'[^\']*\'/i', '', $sanitized);
            $sanitized = preg_replace('/href="javascript:[^"]*"/i', 'href="#"', $sanitized);

            Storage::disk('public')->put('uploads/images/' . $safeName, $sanitized);
            $path = 'storage/uploads/images/' . $safeName;
            $width = 300;
            $height = 300;
        } else {
            $path = $file->storeAs('uploads/images', $safeName, 'public');
            $path = 'storage/' . $path;

            $imageInfo = @getimagesize($file->getRealPath());
            $width = $imageInfo ? $imageInfo[0] : null;
            $height = $imageInfo ? $imageInfo[1] : null;
        }

        $docId = $request->input('document_id');
        if ($docId) {
            \App\Models\Document::where('id', $docId)->where('user_id', Auth::id())->firstOrFail();
        }

        $asset = DocumentAsset::create([
            'user_id' => Auth::id(),
            'document_id' => $docId,
            'session_id' => $request->session()->getId(),
            'name' => $originalName,
            'file_path' => asset($path),
            'file_type' => 'image',
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);

        return response()->json([
            'success' => true,
            'asset' => $asset,
            'url' => asset($path),
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Upload a custom user font (TTF, OTF, WOFF, WOFF2).
     */
    public function uploadFont(Request $request)
    {
        $request->validate([
            'font' => 'required|file|max:10240', // 10MB
            'font_name' => 'required|string|max:100',
        ]);

        $file = $request->file('font');
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['ttf', 'otf', 'woff', 'woff2'];

        if (!in_array($ext, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid font format. Allowed: TTF, OTF, WOFF, WOFF2.',
            ], 422);
        }

        $safeName = Str::slug($request->input('font_name')) . '_' . Str::random(8) . '.' . $ext;
        $path = $file->storeAs('uploads/fonts', $safeName, 'public');

        $font = CustomFont::create([
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'font_name' => $request->input('font_name'),
            'font_family' => $request->input('font_name'),
            'font_weight' => 'normal',
            'font_style' => 'normal',
            'font_file_path' => asset('storage/' . $path),
            'font_format' => $ext,
        ]);

        return response()->json([
            'success' => true,
            'font' => $font,
            'font_url' => asset('storage/' . $path),
            'font_family' => $font->font_family,
        ]);
    }

    /**
     * Delete an asset.
     */
    public function destroy(int $id)
    {
        $asset = DocumentAsset::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted.',
        ]);
    }
}

