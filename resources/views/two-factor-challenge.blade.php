<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificar 2FA</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preload" href="{{ asset('fonts/montserrat-400.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat-500.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/montserrat-700.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <script>
        window.scrollTo = function() {};
        window.requestAnimationFrame = window.requestAnimationFrame || function(cb) { setTimeout(cb, 16); };
        if (document.startViewTransition) {
            document.startViewTransition = function(cb) {
                cb();
                return { finished: Promise.resolve(), ready: Promise.resolve(), skipCallback: function() {} };
            };
        }
    </script>
</head>
<body>
    <x-menu />

    <div class="content">
        <h1>2FA</h1>

        <div class="login-container">
            <p>Introduce el código temporal.</p>

            <div class="input-container">
                <input type="text" name="two_factor_code" id="two_factor_code" placeholder=" " maxlength="6" inputmode="numeric">
                <span class="placeholder">Código de 6 digitos</span>
            </div>

            <p class="error-message"></p>
            <button id="twoFactorLoginButton" data-verify-url="{{ route('two-factor.challenge.store') }}" data-dashboard-url="{{ route('dashboard') }}">Verificar código</button>
        </div>
    </div>

    <script>
window.addEventListener('load', function() {
    var container = document.querySelector('.login-container');
    if (container) {
        container.classList.add('loaded');
    }
});
</script>
    <script src="{{ asset('js/two-factor-challenge.js') }}" defer></script>
</body>
</html>
