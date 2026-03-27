<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preload" href="{{ asset('fonts/montserrat-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat-500.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <x-menu />
    
    <div class="content">
        <h1>Registrarse</h1>

        <div class="register-container">
            <div class="input-container">
                <input type="text" name="username" id="username" placeholder=" ">
                <span class="placeholder">Usuario</span>
            </div>
            <div class="input-container">
                <input type="tel" name="phone_number" id="phone_number" placeholder=" " maxlength="9" inputmode="numeric">
                <span class="placeholder">Número de teléfono</span>
            </div>
            <div class="input-container">
                <input type="password" name="password" id="password" placeholder=" ">
                <span class="placeholder">Contraseña</span>
            </div>
            <div class="input-container">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" ">
                <span class="placeholder">Confirmar contraseña</span>
            </div>

            <button id="registerButton" type="button" data-register-url="{{ route('register.store') }}" data-login-url="{{ route('login') }}">Registrarse</button>
            <p>¿Ya tienes cuenta? <a href="{{ route("login") }}">Iniciar Sesión</a></p>
        </div>
    </div>
    <script src="{{ asset('js/register.js') }}" defer></script>
</body>
</html>
