<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');

        if (!Auth::attempt(['username' => $validated['username'], 'password' => $validated['password']], $remember)) {
            Log::warning('Login fallido', [
                'username' => $validated['username'],
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Credenciales incorrectas.'
                ], 422);
            }

            return back()->withErrors([
                'username' => 'Credenciales incorrectas.'
            ]);
        }

        $request->session()->regenerate();

        Log::info('Login correcto', [
            'user_id' => Auth::id(),
            'username' => $validated['username'],
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login correcto.',
                'redirect' => route('dashboard')
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Logout correcto', [
            'user_id' => $user?->id,
            'username' => $user?->username,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('login');
    }
}
