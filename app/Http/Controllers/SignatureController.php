<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignatureController extends Controller
{
    /**
     * List user signatures.
     */
    public function index(Request $request)
    {
        $signatures = Signature::where('user_id', Auth::id())->latest()->get();

        return response()->json([
            'success' => true,
            'signatures' => $signatures,
        ]);
    }

    /**
     * Save a new signature.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image_data' => 'required|string',
            'name' => 'nullable|string|max:100',
        ]);

        $signature = Signature::create([
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'name' => $request->input('name') ?: 'Signature ' . date('M j, Y'),
            'image_data' => $request->input('image_data'),
        ]);

        return response()->json([
            'success' => true,
            'signature' => $signature,
        ]);
    }

    /**
     * Delete a signature.
     */
    public function destroy(int $id)
    {
        $signature = Signature::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $signature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signature deleted.',
        ]);
    }
}

