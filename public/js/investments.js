(() => {
    function byId(id) {
        return document.getElementById(id);
    }

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    const balanceEl    = byId('balanceValue');
    const buyModal     = byId('buyModal');
    const sellModal    = byId('sellModal');
    const buyForm      = byId('buyForm');
    const sellForm     = byId('sellForm');
    const buyErrorEl   = byId('buyError');
    const sellErrorEl  = byId('sellError');
    const refreshBtn   = byId('refreshPricesBtn');

    // --- Helpers ---

    function formatMoney(amount) {
        return Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function updateBalance(newBalance) {
        if (balanceEl && newBalance != null) {
            balanceEl.textContent = '$' + formatMoney(newBalance);
        }
    }

    function openModal(modal) {
        modal && modal.classList.add('is-open');
    }

    function closeModal(modal) {
        if (!modal) return;
        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        modal.classList.remove('is-open');
    }

    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
    }

    function hideError(el) {
        if (!el) return;
        el.textContent = '';
        el.classList.remove('show');
    }

    // --- Cerrar modales ---

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-close-modal');
            closeModal(byId(id));
        });
    });

    [buyModal, sellModal].forEach((modal) => {
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        closeModal(buyModal);
        closeModal(sellModal);
    });

    // --- Modal compra: abrir ---

    let currentBuyPrice = 0;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open-buy]');
        if (!btn) return;

        const stockId = btn.dataset.openBuy;
        const ticker  = btn.dataset.ticker;
        const name    = btn.dataset.name;
        const price   = parseFloat(btn.dataset.price) || 0;

        currentBuyPrice = price;

        byId('buyStockId').value    = stockId;
        byId('buyStockName').textContent = `${ticker} — ${name}`;
        byId('buyStockPrice').textContent = formatMoney(price);
        byId('buyQuantity').value   = '';
        byId('buyTotal').textContent = '$0.00';
        hideError(buyErrorEl);
        openModal(buyModal);
        setTimeout(() => byId('buyQuantity')?.focus(), 120);
    });

    byId('buyQuantity')?.addEventListener('input', () => {
        const qty = parseInt(byId('buyQuantity').value, 10) || 0;
        byId('buyTotal').textContent = '$' + formatMoney(qty * currentBuyPrice);
    });

    // --- Modal venta: abrir ---

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-open-sell]');
        if (!btn) return;

        const invId   = btn.dataset.openSell;
        const ticker  = btn.dataset.ticker;
        const qty     = btn.dataset.quantity;
        const price   = parseFloat(btn.dataset.currentPrice) || 0;
        const total   = price * (parseInt(qty, 10) || 0);

        byId('sellInvId').value               = invId;
        byId('sellStockName').textContent     = ticker;
        byId('sellQuantity').textContent      = qty;
        byId('sellStockPrice').textContent    = formatMoney(price);
        byId('sellTotal').textContent         = '$' + formatMoney(total);
        hideError(sellErrorEl);
        openModal(sellModal);
    });

    // --- Formulario compra ---

    buyForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError(buyErrorEl);

        const stockId  = byId('buyStockId')?.value;
        const quantity = parseInt(byId('buyQuantity')?.value, 10);

        if (!stockId || !quantity || quantity < 1) {
            showError(buyErrorEl, 'Introduce una cantidad válida.');
            return;
        }

        const submitBtn = buyForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res  = await fetch('/api/investments/buy', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ stock_id: parseInt(stockId, 10), quantity }),
            });

            const data = await res.json();

            if (!res.ok) {
                showError(buyErrorEl, data.message || 'Error al realizar la compra.');
                return;
            }

            closeModal(buyModal);
            updateBalance(data.newBalance);
            addPortfolioRow(data.investment);
        } catch {
            showError(buyErrorEl, 'Error de conexión al realizar la compra.');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // --- Formulario venta ---

    sellForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError(sellErrorEl);

        const invId = byId('sellInvId')?.value;
        if (!invId) return;

        const submitBtn = sellForm.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        try {
            const res  = await fetch(`/api/investments/${invId}/sell`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            const data = await res.json();

            if (!res.ok) {
                showError(sellErrorEl, data.message || 'Error al realizar la venta.');
                return;
            }

            closeModal(sellModal);
            updateBalance(data.newBalance);
            removePortfolioRow(invId);
        } catch {
            showError(sellErrorEl, 'Error de conexión al realizar la venta.');
        } finally {
            submitBtn.disabled = false;
        }
    });

    // --- Actualizar portafolio en DOM ---

    function addPortfolioRow(inv) {
        const portfolioCard = byId('portfolioCard');
        if (!portfolioCard) return;

        const empty = byId('portfolioEmpty');
        if (empty) empty.remove();

        let list = portfolioCard.querySelector('#portfolioList');
        if (!list) {
            list = document.createElement('div');
            list.id = 'portfolioList';
            list.className = 'list-stack';
            portfolioCard.appendChild(list);
        }

        const changeClass = inv.change_percent >= 0 ? 'is-positive' : 'is-negative';
        const changeSign  = inv.change_percent >= 0 ? '+' : '';

        const row = document.createElement('div');
        row.className = 'row-item portfolio-row';
        row.dataset.invId = inv.id;
        row.style.cssText = 'opacity:0;transform:translateY(10px);transition:opacity 0.4s ease,transform 0.4s ease';

        row.innerHTML = `
            <div>
                <p class="stock-ticker">${inv.ticker}</p>
                <small>${inv.quantity} acc · compra $${formatMoney(inv.buy_price)}</small>
            </div>
            <div class="right">
                <p class="amount ${changeClass} portfolio-value" data-inv-id="${inv.id}">$${formatMoney(inv.position_value)}</p>
                <small class="${changeClass} portfolio-change" data-inv-id="${inv.id}">${changeSign}${formatMoney(inv.change_percent)}%</small>
                <button type="button" class="inv-sell-btn"
                    data-open-sell="${inv.id}"
                    data-ticker="${inv.ticker}"
                    data-quantity="${inv.quantity}"
                    data-current-price="${inv.current_price}">Vender</button>
            </div>
        `;

        list.prepend(row);
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 10);
    }

    function removePortfolioRow(invId) {
        const portfolioCard = byId('portfolioCard');
        if (!portfolioCard) return;

        const row = portfolioCard.querySelector(`.portfolio-row[data-inv-id="${invId}"]`);
        if (row) row.remove();

        const remaining = portfolioCard.querySelectorAll('.portfolio-row');
        if (remaining.length === 0 && !byId('portfolioEmpty')) {
            const empty = document.createElement('p');
            empty.id = 'portfolioEmpty';
            empty.className = 'empty-state';
            empty.textContent = 'No tienes inversiones activas.';
            portfolioCard.appendChild(empty);
        }
    }

    // --- Actualizar precios en DOM ---

    function applyPriceUpdates(stocks) {
        stocks.forEach((stock) => {
            // Actualizar precio en el mercado
            document.querySelectorAll(`.stock-price[data-stock-id="${stock.id}"]`).forEach((el) => {
                el.textContent = '$' + formatMoney(stock.current_price);
            });

            // Actualizar variación del día
            const changeSign  = stock.change_pct >= 0 ? '+' : '';
            const changeClass = stock.change_pct >= 0 ? 'is-positive' : 'is-negative';
            const changeAbs   = Math.abs(stock.change ?? 0);
            document.querySelectorAll(`.stock-change[data-change-id="${stock.id}"]`).forEach((el) => {
                el.textContent = `${changeSign}${formatMoney(stock.change_pct)}% (${changeSign}$${formatMoney(changeAbs)})`;
                el.className   = `stock-change ${changeClass}`;
            });

            // Actualizar data-price en botones de compra
            document.querySelectorAll(`[data-open-buy="${stock.id}"]`).forEach((btn) => {
                btn.dataset.price = stock.current_price;
            });

            // Actualizar portafolio: recalcular valor y % vs precio de compra
            document.querySelectorAll(`[data-open-sell]`).forEach((btn) => {
                if (btn.dataset.ticker !== stock.ticker) return;

                const invId = btn.dataset.openSell;
                const qty   = parseInt(btn.dataset.quantity, 10) || 0;
                btn.dataset.currentPrice = stock.current_price;

                const valEl    = document.querySelector(`.portfolio-value[data-inv-id="${invId}"]`);
                const pctEl    = document.querySelector(`.portfolio-change[data-inv-id="${invId}"]`);
                const row      = document.querySelector(`.portfolio-row[data-inv-id="${invId}"]`);
                if (!valEl || !row) return;

                const buyMatch = row.querySelector('small')?.textContent.match(/compra \$([0-9,.]+)/);
                const buyPrice = buyMatch ? parseFloat(buyMatch[1].replace(/,/g, '')) : 0;

                const posValue  = stock.current_price * qty;
                const pct       = buyPrice > 0 ? ((stock.current_price - buyPrice) / buyPrice) * 100 : 0;
                const pctSign   = pct >= 0 ? '+' : '';
                const pctClass  = pct >= 0 ? 'is-positive' : 'is-negative';

                valEl.textContent = '$' + formatMoney(posValue);
                valEl.className   = `amount ${pctClass} portfolio-value`;

                if (pctEl) {
                    pctEl.textContent = `${pctSign}${formatMoney(pct)}%`;
                    pctEl.className   = `${pctClass} portfolio-change`;
                }
            });
        });
    }

    // --- TradingView widget ---

    let currentChartTicker = window.INVESTMENTS_DEFAULT_TICKER || 'AAPL';

    function loadChart(ticker) {
        currentChartTicker = ticker;
        const wrap = byId('tvWidgetContainer');
        if (!wrap) return;

        // Vaciar el contenedor y recrearlo para forzar reinicio del widget
        wrap.innerHTML = `
            <div class="tradingview-widget-container__widget" style="height:calc(100% - 32px);width:100%"></div>
            <div class="tradingview-widget-copyright">
                <a href="https://www.tradingview.com/" rel="noopener nofollow" target="_blank">
                    <span class="blue-text">Track all markets on TradingView</span>
                </a>
            </div>
        `;

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
        script.async = true;
        script.textContent = JSON.stringify({
            allow_symbol_change: true,
            calendar: false,
            details: false,
            hide_side_toolbar: true,
            hide_top_toolbar: false,
            hide_legend: false,
            hide_volume: false,
            hotlist: false,
            interval: 'D',
            locale: 'en',
            save_image: true,
            style: '1',
            symbol: `NASDAQ:${ticker}`,
            theme: 'dark',
            timezone: 'Etc/UTC',
            backgroundColor: '#0F0F0F',
            gridColor: 'rgba(242, 242, 242, 0.06)',
            watchlist: [],
            withdateranges: false,
            compareSymbols: [],
            studies: [],
            autosize: true,
        });

        wrap.appendChild(script);
    }

    // Cargar gráfica con el primer ticker al inicio
    loadChart(currentChartTicker);

    // Cambiar gráfica al hacer click en una fila del mercado
    document.addEventListener('click', (e) => {
        const row = e.target.closest('.stock-row');
        if (!row) return;

        // No activar si el click fue en el botón comprar
        if (e.target.closest('.inv-buy-btn')) return;

        const ticker = row.querySelector('.stock-ticker')?.textContent?.trim();
        if (ticker && ticker !== currentChartTicker) {
            loadChart(ticker);

            // Marcar fila activa
            document.querySelectorAll('.stock-row').forEach((r) => r.classList.remove('is-chart-active'));
            row.classList.add('is-chart-active');
        }
    });

    // --- Botón actualizar precios ---

    refreshBtn?.addEventListener('click', async () => {
        refreshBtn.disabled = true;

        try {
            const res  = await fetch('/api/investments/prices', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });

            if (!res.ok) return;

            const data = await res.json();
            applyPriceUpdates(data.stocks ?? []);
        } catch {
            // Silenciar errores de red
        } finally {
            refreshBtn.disabled = false;
        }
    });
})();
