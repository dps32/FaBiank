<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
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
            'password.min'=>'La contrasena debe tener al menos 8 caracteres.',
            'password.confirmed'=>'La confirmacion de contraseña no coincide.',   
        ]);
        User::create([
            'username'=>$validated['username'],
            'phone_number'=>$validated['phone_number'],
            'password'=>$validated['password'],
            'balance'=>0,
            'is_verified'=>false,
            'sms_code'=>null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Cuenta creada correctamente.'
            ]);
        }

        return redirect()->route('login')->with('status','Cuenta creada correctamente.');
    }
}
