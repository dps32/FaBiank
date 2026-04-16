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
            'name'=>['required', 'string', 'min:3', 'max:120'],
            'username'=>['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'password'=>['required','string','min:8','confirmed'],
        ], [
            'name.required'=>'El nombre completo es obligatorio.',
            'username.unique'=>'Este usuario ya existe. Cambia el nombre o logeate.',
            'password.min'=>'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'=>'La confirmación de contraseña no coincide.',
        ]);
        $user = User::create([
            'name'=>$validated['name'],
            'username'=>$validated['username'],
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
