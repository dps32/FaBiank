<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inversiones</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <link rel="stylesheet" href="{{ asset('css/search-users.css') }}">
    <link rel="stylesheet" href="{{ asset('css/investments.css') }}">
</head>
<body>
    <x-menu />

    <div class="content investments-content">
        <div class="inv-header">
            <div>
                <h1 class="inv-title">Inversiones</h1>
                <p class="inv-subtitle">Saldo disponible: <span id="balanceValue">${{ number_format($balance, 2) }}</span></p>
            </div>
        </div>

        <div class="inv-layout">
            <aside class="card inv-sidebar" id="marketCard" style="transition-delay:0.05s">
                <div class="card-title-row inv-sidebar-header">
                    <h2>Mercado</h2>
                    <button type="button" id="refreshPricesBtn" class="inv-refresh-btn" title="Actualizar precios">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        Actualizar
                    </button>
                </div>

                @if ($stocks->isNotEmpty())
                    <div id="stocksList">
                        @foreach ($stocks as $index => $stock)
                            @php
                                $changePct = $stock['change_pct'] ?? 0;
                                $change = $stock['change'] ?? 0;
                                $changeClass = $changePct >= 0 ? 'is-positive' : 'is-negative';
                                $changeSign = $changePct >= 0 ? '+' : '';
                            @endphp
                            <div class="stock-row" data-stock-id="{{ $stock['id'] }}" style="transition-delay: {{ 0.1 + ($index * 0.04) }}s">
                                <div class="stock-row-top">
                                    <div class="stock-id-info">
                                        <span class="stock-ticker">{{ $stock['ticker'] }}</span>
                                        <span class="stock-name">{{ $stock['name'] }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="inv-buy-btn"
                                        data-open-buy="{{ $stock['id'] }}"
                                        data-ticker="{{ $stock['ticker'] }}"
                                        data-name="{{ $stock['name'] }}"
                                        data-price="{{ $stock['current_price'] }}"
                                    >Comprar</button>
                                </div>
                                <div class="stock-row-prices">
                                    <span class="stock-price" data-stock-id="{{ $stock['id'] }}">${{ number_format((float)$stock['current_price'], 2) }}</span>
                                    <span class="stock-change {{ $changeClass }}" data-change-id="{{ $stock['id'] }}">{{ $changeSign }}{{ number_format($changePct, 2) }}% ({{ $changeSign }}${{ number_format(abs($change), 2) }})</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="empty-state">No hay acciones disponibles.</p>
                @endif
            </aside>

            <div class="inv-right">
                <div class="inv-chart-wrap card" id="chartCard" style="transition-delay:0.1s">
                    <div id="tvWidgetContainer" class="tradingview-widget-container" style="height:100%;width:100%">
                        <div class="tradingview-widget-container__widget" style="height:calc(100% - 32px);width:100%"></div>
                    </div>
                </div>

                <div class="inv-bottom-grid">
                    <article class="card inv-card" id="portfolioCard" style="transition-delay:0.15s">
                        <div class="card-title-row">
                            <h2>Mi portafolio</h2>
                        </div>
                        @if ($portfolio->isNotEmpty())
                            <div class="list-stack" id="portfolioList">
                                @foreach ($portfolio as $index => $inv)
                                    @php
                                        $portfolioClass = $inv['gain_loss'] >= 0 ? 'is-positive' : 'is-negative';
                                        $portfolioSign = $inv['gain_loss'] >= 0 ? '+' : '-';
                                    @endphp
                                    <div class="row-item portfolio-row investment-row" data-inv-id="{{ $inv['id'] }}" data-buy-price="{{ $inv['buy_price'] }}" style="transition-delay: {{ 0.2 + ($index * 0.04) }}s">
                                        <div class="investment-main">
                                            <div>
                                                <p class="stock-ticker">{{ $inv['ticker'] }}</p>
                                                <small>{{ $inv['name'] }}</small>
                                            </div>
                                            <div class="investment-meta">
                                                <span>{{ $inv['quantity'] }} acc</span>
                                                <span>Compra ${{ number_format($inv['buy_price'], 2) }}</span>
                                                <span class="portfolio-current-price" data-inv-id="{{ $inv['id'] }}">Actual ${{ number_format($inv['current_price'], 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="investment-money">
                                            <span class="investment-label">Valor actual</span>
                                            <p class="amount portfolio-value" data-inv-id="{{ $inv['id'] }}">
                                                ${{ number_format($inv['position_value'], 2) }}
                                            </p>
                                            <p class="investment-gain {{ $portfolioClass }} portfolio-gain" data-inv-id="{{ $inv['id'] }}">
                                                {{ $portfolioSign }}${{ number_format(abs($inv['gain_loss']), 2) }}
                                            </p>
                                            <small class="{{ $portfolioClass }} portfolio-change" data-inv-id="{{ $inv['id'] }}">
                                                {{ $inv['change_percent'] >= 0 ? '+' : '' }}{{ number_format($inv['change_percent'], 2) }}%
                                            </small>
                                            <button
                                                type="button"
                                                class="inv-sell-btn"
                                                data-open-sell="{{ $inv['id'] }}"
                                                data-ticker="{{ $inv['ticker'] }}"
                                                data-quantity="{{ $inv['quantity'] }}"
                                                data-current-price="{{ $inv['current_price'] }}"
                                            >Vender</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="empty-state" id="portfolioEmpty">No tienes inversiones activas.</p>
                        @endif
                    </article>

                    <article class="card inv-card" id="historyCard" style="transition-delay:0.2s">
                        <div class="card-title-row">
                            <h2>Historial</h2>
                        </div>
                        @if ($history->isNotEmpty())
                            <div class="list-stack">
                                @foreach ($history as $index => $inv)
                                    @php
                                        $historyClass = $inv['gain_loss'] >= 0 ? 'is-positive' : 'is-negative';
                                        $historySign = $inv['gain_loss'] >= 0 ? '+' : '-';
                                    @endphp
                                    <div class="row-item investment-row history-investment-row" style="transition-delay: {{ 0.25 + ($index * 0.04) }}s">
                                        <div class="investment-main">
                                            <div>
                                                <p class="stock-ticker">{{ $inv['ticker'] }}</p>
                                                <small>{{ $inv['name'] }}</small>
                                            </div>
                                            <div class="investment-meta">
                                                <span>{{ $inv['quantity'] }} acc</span>
                                                <span>Compra ${{ number_format($inv['buy_price'], 2) }}</span>
                                                <span>Venta ${{ number_format($inv['sell_price'] ?? 0, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="investment-money">
                                            <span class="investment-label">Ganancia / perdida</span>
                                            <p class="amount {{ $historyClass }}">
                                                {{ $historySign }}${{ number_format(abs($inv['gain_loss']), 2) }}
                                            </p>
                                            <small class="{{ $historyClass }}">
                                                {{ $inv['change_percent'] >= 0 ? '+' : '' }}{{ number_format($inv['change_percent'], 2) }}%
                                            </small>
                                            <span class="investment-total">Recibido ${{ number_format($inv['position_value'], 2) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="empty-state">No has realizado ventas aun.</p>
                        @endif
                    </article>
                </div>
            </div>
        </div>

        <div id="buyModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3>Comprar acciones</h3>
                    <button type="button" class="modal-close" data-close-modal="buyModal">Cerrar</button>
                </div>
                <form id="buyForm" class="send-form">
                    <p class="modal-recipient-label">
                        <span id="buyStockName"></span>
                        <span class="inv-modal-price"> - $<span id="buyStockPrice"></span> por accion</span>
                    </p>
                    <input type="hidden" id="buyStockId" name="stock_id">
                    <div class="form-group">
                        <label for="buyQuantity">Cantidad de acciones</label>
                        <input type="number" id="buyQuantity" name="quantity" placeholder="1" min="1" step="1" required>
                    </div>
                    <p class="inv-total-label">Total: <strong id="buyTotal">$0.00</strong></p>
                    <button type="submit" class="form-submit">Confirmar compra</button>
                    <p id="buyError" class="form-error"></p>
                </form>
            </div>
        </div>

        <div id="sellModal" class="modal">
            <div class="modal-panel">
                <div class="modal-header">
                    <h3>Vender acciones</h3>
                    <button type="button" class="modal-close" data-close-modal="sellModal">Cerrar</button>
                </div>
                <form id="sellForm" class="send-form">
                    <p class="modal-recipient-label">
                        <span id="sellStockName"></span>
                        <span class="inv-modal-price"> - <span id="sellQuantity"></span> acc - precio actual $<span id="sellStockPrice"></span></span>
                    </p>
                    <input type="hidden" id="sellInvId" name="investment_id">
                    <p class="inv-total-label">Recibiras: <strong id="sellTotal">$0.00</strong></p>
                    <button type="submit" class="form-submit">Confirmar venta</button>
                    <p id="sellError" class="form-error"></p>
                </form>
            </div>
        </div>
    </div>

    <button id="logoutButton" type="button"
        data-logout-url="{{ route('logout') }}"
        data-login-url="{{ route('login') }}"
        data-prices-url="{{ route('api.investments.prices') }}"
        data-portfolio-prices-url="{{ route('api.investments.portfolio-prices') }}"
        data-buy-url="{{ route('api.investments.buy') }}"
        data-sell-url-template="{{ route('api.investments.sell', ['investmentId' => '__ID__']) }}"
        style="display:none">Deslogearse</button>

    <script>
    window.addEventListener('load', function() {
        document.querySelector('.investments-content')?.classList.add('loaded');
        document.querySelectorAll('.card').forEach(function(card) {
            card.classList.add('loaded');
        });
        document.querySelectorAll('.row-item, .stock-row').forEach(function(el) {
            el.classList.add('loaded');
        });
    });
    </script>

    <script>
        window.INVESTMENTS_DEFAULT_TICKER = '{{ $stocks->first()['ticker'] ?? 'AAPL' }}';
    </script>
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
    <script src="{{ asset('js/investments.js') }}" defer></script>
</body>
</html>
