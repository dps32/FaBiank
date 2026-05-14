<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function __construct(private TwoFactorService $twoFactorService)
    {
    }

    // api de login
    public function store(Request $request)
    {
        // validar que viene el user y pass
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // buscamos si hay un usuario con ese username
        $user = User::query()->where('username', $validated['username'])->first();

        // si no hay usuario o ese usuario no tiene la contraseña recibida devolvemos error
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.'
            ], 403);
        }

        $request->session()->regenerate(); // regeneramos la sesión

        // si no tiene configurado el 2fa, lo llevamos a configurar
        if (!$this->twoFactorService->hasConfiguredSecret($user->two_factor_secret)) {
            $request->session()->put('two_factor_setup', [
                'user_id' => $user->id,
                'secret' => $this->twoFactorService->generateSecret(),
            ]);

            return response()->json([
                'redirect' => route('two-factor.setup.show'),
            ]);
        }

        $request->session()->put('two_factor_login', [
            'user_id' => $user->id,
        ]);

        Log::info('Credenciales correctas, pendiente de código 2FA', [
            'user_id' => $user->id,
            'username' => $validated['username'],
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Introduce el código de Authenticator.',
            'redirect' => route('two-factor.challenge.show'),
        ]);
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logout correcto.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }
}
