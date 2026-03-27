<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Investments</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
</head>
<body>
    <x-menu />

    <div class="content">
        <h1>Inversiones</h1>
        <p>Has iniciado sesion correctamente.</p>

        <button id="logoutButton" type="button" data-logout-url="{{ route('logout') }}" data-login-url="{{ route('login') }}">Deslogearse</button>
    </div>

    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</body>
</html>
