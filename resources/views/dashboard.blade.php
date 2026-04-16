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
    <link rel="stylesheet" href="{{ asset('css/search-users.css') }}">
</head>
<body>
    <x-menu />

    <div class="content">
        <section class="dashboard-grid">
            <div class="dashboard-left">
                <div class="dashboard-top">
                    <article class="card balance-card">
                        <p class="card-label">Saldo disponible</p>
                        <p id="balanceValue" class="balance-value">${{ number_format($balance, 2) }}</p>
                    </article>

                    <article class="actions-card">
                        <button class="action-btn" type="button" id="openSendModal">Enviar</button>
                        <button class="action-btn" type="button" id="openReceiveModal">Recibir</button>
                    </article>
                </div>

                <article id="transactionsCard" class="card transactions-card">
                    <div class="card-title-row">
                        <h2>Últimas transacciones</h2>
                        <a href="{{ route('history') }}">Ver todas</a>
                    </div>

                    @if ($transactionItems->isNotEmpty())
                        <div id="transactionsList" class="list-stack">
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
                        <p id="transactionsEmpty" class="empty-state">Aqui no hay nada que mostrar.</p>
                    @endif
                </article>

                <article id="paymentRequestsCard" class="card payment-requests-card">
                    <div class="card-title-row">
                        <h2>Solicitudes recibidas</h2>
                    </div>

                    @if ($paymentRequestItems->isNotEmpty())
                        <div id="paymentRequestsList" class="list-stack">
                            @foreach ($paymentRequestItems as $item)
                                <div class="row-item request-row" data-request-id="{{ $item['id'] }}">
                                    <div>
                                        <p>{{ $item['requester'] }}</p>
                                        <small>{{ $item['date'] }}</small>
                                    </div>
                                    <div class="right request-actions-wrap">
                                        <p class="amount">{{ $item['amountLabel'] }}</p>
                                        <div class="request-actions">
                                            <button type="button" class="request-btn is-accept" data-request-action="accept" data-request-id="{{ $item['id'] }}" data-request-amount="{{ $item['amount'] }}" data-requester="{{ $item['requester'] }}">Aceptar</button>
                                            <button type="button" class="request-btn is-reject" data-request-action="reject" data-request-id="{{ $item['id'] }}">Rechazar</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p id="paymentRequestsEmpty" class="empty-state">No tienes solicitudes pendientes.</p>
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
                    <h3 id="sendModalTitle">Enviar dinero</h3>
                    <button type="button" class="modal-close" data-close-modal="sendModal">Cerrar</button>
                </div>

                <form id="sendMoneyForm" class="send-form">
                    <div class="form-group">
                        <label for="recipientUsername">Usuario destinatario</label>
                        <input
                            type="text"
                            id="recipientUsername"
                            name="recipientUsername"
                            placeholder="Nombre del usuario"
                            required
                        >
                        <input type="hidden" id="recipientId" name="recipientId" value="">
                    </div>

                    <div class="form-group">
                        <label for="transferAmount">Cantidad</label>
                        <input
                            type="number"
                            id="transferAmount"
                            name="transferAmount"
                            placeholder="0.00"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <button type="submit" class="form-submit">Enviar</button>
                    <p id="sendError" class="form-error"></p>
                </form>
            </div>
        </div>

        <div id="receiveModal" class="modal" aria-hidden="true">
            <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="receiveModalTitle">
                <div class="modal-header">
                    <h3 id="receiveModalTitle">Solicitar pago</h3>
                    <button type="button" class="modal-close" data-close-modal="receiveModal">Cerrar</button>
                </div>

                <form id="requestPaymentForm" class="receive-form">
                    <div class="form-group">
                        <label for="requestTargetUsername">Usuario destinatario</label>
                        <input
                            type="text"
                            id="requestTargetUsername"
                            name="targetUsername"
                            placeholder="Nombre del usuario"
                            required
                        >
                        <input type="hidden" id="requestTargetId" name="targetId" value="">
                    </div>

                    <div class="form-group">
                        <label for="requestAmount">Cantidad</label>
                        <input
                            type="number"
                            id="requestAmount"
                            name="requestAmount"
                            placeholder="0.00"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <button type="submit" class="form-submit">Solicitar</button>
                    <p id="receiveError" class="form-error"></p>
                </form>
            </div>
        </div>
    </div>

    <button id="logoutButton" type="button" data-logout-url="{{ route('logout') }}" data-login-url="{{ route('login') }}" style="display: none;">Deslogearse</button>

    <script src="{{ asset('js/dashboard.js') }}" defer></script>
</body>
</html>
