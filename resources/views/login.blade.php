<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
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
        <h1>Iniciar sesión</h1>
        
        <div class="login-container">
            <input type="text" name="" id="username" placeholder="Usuario">
            <input type="password" name="" id="password" placeholder="Contraseña">
            <button id="loginButton">Iniciar Sesión</button>
            <p>¿No tienes cuenta? <a href="{{ route("register") }}">Registrarse</a></p>
        </div>
    </div>
</body>
</html>
