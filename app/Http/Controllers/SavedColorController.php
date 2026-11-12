<?php

namespace App\Http\Controllers;

use App\Models\SavedColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedColorController extends Controller
{
    public function index(Request $request)
    {
        $colors = SavedColor::where('user_id', Auth::id())->latest()->get();

        return response()->json([
            'success' => true,
            'colors' => $colors,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'color_code' => 'required|string|max:50',
            'color_type' => 'nullable|string|max:30',
            'name' => 'nullable|string|max:100',
        ]);

        $userId = Auth::id();

        $color = SavedColor::firstOrCreate([
            'user_id' => $userId,
            'color_code' => $request->input('color_code'),
        ], [
            'session_id' => $request->session()->getId(),
            'color_type' => $request->input('color_type', 'hex'),
            'name' => $request->input('name'),
        ]);

        return response()->json([
            'success' => true,
            'color' => $color,
        ]);
    }

    public function destroy(int $id)
    {
        $color = SavedColor::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $color->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}

