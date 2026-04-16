<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function __construct(private TwoFactorService $twoFactorService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username'=>['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'phone_number'=>['required', 'regex:/^\d{9}$/', 'unique:users,phone_number'],
            'password'=>['required','string','min:8','confirmed'],
        ], [
            'username.unique'=>'Este usuario ya existe. Cambia el nombre o logeate.',
            'phone_number.regex'=>'El telefono tiene que tener 9 digitos.',
            'phone_number.unique'=>'Este telefono ya existe.',
            'password.min'=>'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'=>'La confirmación de contraseña no coincide.',
        ]);

        $user = User::create([
            'username'=>$validated['username'],
            'phone_number'=>$validated['phone_number'],
            'password'=>$validated['password'],
            'balance'=>0,
            'two_factor_secret'=>null,
        ]);

        $request->session()->put('two_factor_setup', [
            'user_id' => $user->id,
            'secret' => $this->twoFactorService->generateSecret(),
        ]);

        return response()->json([
            'message' => 'Cuenta creada correctamente. Configura tu doble factor.',
            'redirect' => route('two-factor.setup.show'),
        ]);
    }
}
