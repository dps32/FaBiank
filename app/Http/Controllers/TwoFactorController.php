<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    public function __construct(private TwoFactorService $twoFactorService)
    {
    }

    // página de setup de 2fa
    public function setupCreate(Request $request)
    {
        // comprobar que hay una sesión de setup 2fa
        $setup = $this->getSetupSession($request);

        // si no hay lo mandamos al login
        if (!$setup) return redirect()->route('login');

        $user = User::query()->find($setup['user_id']);

        // si no hay usuario, borramos la sesión para no liarla
        if (!$user) {
            $request->session()->forget('two_factor_setup');

            return redirect()->route('login');
        }

        // generamos el qr y mostramos la página de setup
        $issuer = (string) config('app.name', 'FaBiank');
        $otpauthUri = $this->twoFactorService->buildOtpAuthUri($setup['secret'], $user->username, $issuer);
        $qrUrl = $this->twoFactorService->buildQrCodeUrl($otpauthUri);

        return view('two-factor-setup', [
            'qrUrl' => $qrUrl,
            'secret' => $setup['secret'],
            'username' => $user->username,
        ]);
    }

    // guardar la configuración del 2fa
    public function setupStore(Request $request)
    {
        // validar formato correcto
        $validated = $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'El código debe tener 6 digitos.',
        ]);

        // obtener la sesión del setup
        $setup = $this->getSetupSession($request);

        if (!$setup) {
            return response()->json([
                'message' => 'La configuración 2FA ha expirado. Inicia sesión de nuevo.'
            ], 419);
        }

        // obtener el usuario
        $user = User::query()->find($setup['user_id']);

        if (!$user) {
            $request->session()->forget('two_factor_setup');

            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        // comprobar que el código 2fa es válido
        if (!$this->twoFactorService->verifyCode($setup['secret'], $validated['code'])) {
            return response()->json([
                'message' => 'Código 2FA incorrecto.'
            ], 422);
        }

        // guardar el secret 2fa en el usuario
        $user->two_factor_secret = $setup['secret'];
        $user->save();
        $request->session()->forget('two_factor_setup');

        Log::info('Configuración 2FA completada', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Doble factor configurado correctamente.',
            'redirect' => route('login'),
        ]);
    }

    // mostrar la página con 2fa
    public function challengeCreate(Request $request)
    {
        $challenge = $this->getLoginChallenge($request);

        if (!$challenge) {
            return redirect()->route('login');
        }

        return view('two-factor-challenge');
    }

    // procesar el código 2fa
    public function challengeStore(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'code.regex' => 'El código debe tener 6 digitos.',
        ]);

        // obtener la sesión del challenge del login
        $challenge = $this->getLoginChallenge($request);

        if (!$challenge) {
            return response()->json([
                'message' => 'La verificación 2FA ha expirado. Inicia sesión de nuevo.'
            ], 419);
        }

        $user = User::query()->find($challenge['user_id']);

        // asegurarse que el usuario tiene el 2fa configurado
        if (!$user || !$this->twoFactorService->hasConfiguredSecret($user->two_factor_secret)) {
            $request->session()->forget('two_factor_login');

            return response()->json([
                'message' => 'Usuario sin 2FA configurado.'
            ], 422);
        }

        // comprobar código 2fa
        if (!$this->twoFactorService->verifyCode($user->two_factor_secret, $validated['code'])) {
            return response()->json([
                'message' => 'Código 2FA incorrecto.'
            ], 422);
        }

        // inciar la sesión
        $request->session()->forget('two_factor_login');
        Auth::login($user);
        $request->session()->regenerate();

        Log::info('Login con 2FA correcto', [
            'user_id' => $user->id,
            'username' => $user->username,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Login correcto.',
            'redirect' => route('dashboard'),
        ]);
    }


    // abrir sesión de setup 2fa
    private function getSetupSession(Request $request): ?array
    {
        $setup = $request->session()->get('two_factor_setup');

        if (!is_array($setup)) {
            return null;
        }

        $userId = $setup['user_id'] ?? null;
        $secret = $setup['secret'] ?? null;

        if ((!is_int($userId) && !ctype_digit((string) $userId)) || !is_string($secret) || $secret === '') {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'secret' => $secret,
        ];
    }

    // abrir sesión de 2fa para el login
    private function getLoginChallenge(Request $request): ?array
    {
        $challenge = $request->session()->get('two_factor_login');

        if (!is_array($challenge)) {
            return null;
        }

        $userId = $challenge['user_id'] ?? null;

        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
        ];
    }
}
