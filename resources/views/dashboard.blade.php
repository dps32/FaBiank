<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <x-menu />

    <div class="content">
        <section class="dashboard-grid">
            <div class="dashboard-left">
                <div class="dashboard-top">
                    <article class="card balance-card">
                        <p class="card-label">Saldo disponible</p>
                        <p class="balance-value">${{ number_format($balance, 2) }}</p>
                    </article>

                    <article class="actions-card">
                        <button class="action-btn" type="button" id="openSendModal">Enviar</button>
                        <button class="action-btn" type="button" id="openReceiveModal">Recibir</button>
                    </article>
                </div>

                <article class="card transactions-card">
                    <div class="card-title-row">
                        <h2>Ultimas transacciones</h2>
                        <a href="{{ route('history') }}">Ver todas</a>
                    </div>

                    @if ($transactionItems->isNotEmpty())
                        <div class="list-stack">
                            @foreach ($transactionItems as $item)
                                <div class="row-item">
                                    <p>{{ $item['counterparty'] }}</p>
                                    <p class="amount {{ $item['amountClass'] }}">
                                        {{ $item['signedAmount'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="empty-state">Aqui no hay nada que mostrar.</p>
                    @endif
                </article>
            </div>

            <article class="card investments-card">
                <div class="card-title-row">
                    <h2>Resumen inversiones</h2>
                    <a href="{{ route('investments') }}">Ver todas</a>
                </div>

                @if ($investmentItems->isNotEmpty())
                    <div class="list-stack">
                        @foreach ($investmentItems as $item)
                            <div class="row-item">
                                <div>
                                    <p>{{ $item['name'] }}</p>
                                    <small>${{ number_format($item['buyPrice'], 2) }} compra</small>
                                </div>
                                <div class="right">
                                    <p class="amount {{ $item['amountClass'] }}">
                                        ${{ number_format($item['positionValue'], 2) }}
                                    </p>
                                    <small class="{{ $item['amountClass'] }}">
                                        {{ $item['changePercent'] >= 0 ? '+' : '' }}{{ number_format($item['changePercent'], 2) }}%
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="empty-state">Aqui no hay nada que mostrar.</p>
                @endif
            </article>
        </section>


        <div id="sendModal" class="modal" aria-hidden="true">
            <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="sendModalTitle">
                <div class="modal-header">
                    <h3 id="sendModalTitle">Enviar</h3>
                    <button type="button" class="modal-close" data-close-modal="sendModal">Cerrar</button>
                </div>
                <p>TODO: enviar dinero.</p>
            </div>
        </div>

        <div id="receiveModal" class="modal" aria-hidden="true">
            <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="receiveModalTitle">
                <div class="modal-header">
                    <h3 id="receiveModalTitle">Recibir</h3>
                    <button type="button" class="modal-close" data-close-modal="receiveModal">Cerrar</button>
                </div>
                <p>TODO: recibir dinero.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</body>
</html>
