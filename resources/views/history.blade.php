<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>History</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/search-users.css') }}">
    <link rel="stylesheet" href="{{ asset('css/history.css') }}">
</head>
<body>
    <x-menu />

    <div class="history-wrap">
        <article class="history-card">
            @if ($historySections->isNotEmpty())
                @foreach ($historySections as $section)
                    <section class="history-section">
                        <h2 class="history-section-title">{{ $section['label'] }}</h2>

                        <div class="history-list">
                            @foreach ($section['items'] as $item)
                                <article class="history-item">
                                    <div class="history-item-dot" aria-hidden="true"></div>

                                    <div class="history-item-main">
                                        <p class="history-item-title">{{ $item['movementLabel'] }}</p>
                                        <p class="history-item-subtitle">
                                            {{ $item['counterparty'] }} -
                                            <span class="history-item-time" data-occurred-at-utc="{{ $item['occurredAtUtc'] }}">{{ $item['timeLabel'] }}</span>
                                        </p>
                                        <p class="history-item-balance">Balance: {{ $item['resultingBalance'] }}</p>
                                    </div>

                                    <p class="history-item-amount {{ $item['amountClass'] }}">{{ $item['signedAmount'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @else
                <p class="empty-state">Todavia no tienes movimientos para mostrar.</p>
            @endif
        </article>
    </div>

    <button id="logoutButton" type="button" data-logout-url="{{ route('logout') }}" data-login-url="{{ route('login') }}" style="display: none;">Deslogearse</button>
    <script src="{{ asset('js/history.js') }}" defer></script>
</body>
</html>
