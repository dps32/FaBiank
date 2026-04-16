<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configurar 2FA</title>
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
        <h1>Configurar 2FA</h1>

        <div class="login-container">
            <p>Escanea este QR con tu app de autenticación</p>
            <img class="two-factor-qr" src="{{ $qrUrl }}">
            <p class="two-factor-secret">Secret: {{ $secret }}</p>

            <div class="input-container">
                <input type="text" name="two_factor_code" id="two_factor_code" placeholder=" " maxlength="6" inputmode="numeric">
                <span class="placeholder">Código de 6 digitos</span>
            </div>

            <p class="error-message"></p>
            <button id="twoFactorSetupButton" data-setup-url="{{ route('two-factor.setup.store') }}" data-login-url="{{ route('login') }}">Confirmar 2FA</button>
        </div>
    </div>

    <script src="{{ asset('js/two-factor-setup.js') }}" defer></script>
</body>
</html>
