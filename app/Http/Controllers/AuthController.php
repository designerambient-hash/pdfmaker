<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAsset;
use App\Models\SavedColor;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $sessionId = $request->session()->getId();
            $request->session()->regenerate();
            $user = Auth::user();

            // Claim any documents created in this session prior to login
            $this->migrateSessionData($sessionId, $user->id);

            return redirect()->intended(route('dashboard'))
                ->with('toast_success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Show registration page.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Handle new user registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $sessionId = $request->session()->getId();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // Claim any documents created in this session prior to registration
        $this->migrateSessionData($sessionId, $user->id);

        return redirect()->route('dashboard')
            ->with('toast_success', 'Account created successfully! Welcome to PDFCraft Studio.');
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('toast_info', 'You have been signed out.');
    }

    /**
     * Migrate guest session documents and assets to authenticated user.
     */
    protected function migrateSessionData(string $sessionId, int $userId): void
    {
        if (empty($sessionId)) return;

        Document::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);

        DocumentAsset::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);

        Signature::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);

        SavedColor::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update(['user_id' => $userId]);
    }
}

