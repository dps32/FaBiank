(() => {
    function byId(id) {
        return document.getElementById(id);
    }

    const logoutButton = document.getElementById('logoutButton');
    const sendModal = byId('sendModal');
    const receiveModal = byId('receiveModal');
    const sendMoneyForm = byId('sendMoneyForm');
    const requestPaymentForm = byId('requestPaymentForm');
    const recipientUsernameInput = byId('recipientUsername');
    const recipientIdInput = byId('recipientId');
    const requestTargetUsernameInput = byId('requestTargetUsername');
    const requestTargetIdInput = byId('requestTargetId');
    const sendErrorElement = byId('sendError');
    const receiveErrorElement = byId('receiveError');
    const paymentRequestErrorElement = byId('paymentRequestError');
    const balanceValueElement = byId('balanceValue');
    const transactionsCard = byId('transactionsCard');
    const paymentRequestsCard = byId('paymentRequestsCard');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    const logoutUrl = logoutButton?.dataset.logoutUrl ?? '';
    const loginUrl = logoutButton?.dataset.loginUrl ?? '/login';

    function formatMoney(amount) {
        return Number(amount || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function updateBalance(newBalance) {
        if (!balanceValueElement || typeof newBalance === 'undefined' || newBalance === null) {
            return;
        }

        balanceValueElement.textContent = `$${formatMoney(newBalance)}`;
    }

    function ensureTransactionsList() {
        if (!transactionsCard) {
            return null;
        }

        let list = byId('transactionsList');
        if (list) {
            return list;
        }

        list = document.createElement('div');
        list.id = 'transactionsList';
        list.className = 'list-stack';
        transactionsCard.appendChild(list);
        return list;
    }

    function prependTransaction(counterparty, amount, incoming = false) {
        const list = ensureTransactionsList();
        if (!list) {
            return;
        }

        const empty = byId('transactionsEmpty');
        if (empty) {
            empty.remove();
        }

        const row = document.createElement('div');
        row.className = 'row-item';
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        row.style.transition = 'opacity 0.4s ease, transform 0.4s ease';

        const userNode = document.createElement('p');
        userNode.textContent = (counterparty || 'Usuario').trim() || 'Usuario';

        const amountNode = document.createElement('p');
        amountNode.className = `amount ${incoming ? 'is-positive' : 'is-negative'}`;
        amountNode.textContent = `${incoming ? '+' : '-'} $${formatMoney(amount)}`;

        row.appendChild(userNode);
        row.appendChild(amountNode);
        list.prepend(row);

        setTimeout(function() {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 10);

        const rows = list.querySelectorAll('.row-item');
        if (rows.length > 5) {
            rows[rows.length - 1].remove();
        }
    }

    function removePaymentRequestRow(requestId) {
        if (!paymentRequestsCard) {
            return;
        }

        const row = paymentRequestsCard.querySelector(`.request-row[data-request-id="${requestId}"]`);
        if (row) {
            row.remove();
        }

        const remaining = paymentRequestsCard.querySelectorAll('.request-row');
        if (remaining.length === 0 && !byId('paymentRequestsEmpty')) {
            const empty = document.createElement('p');
            empty.id = 'paymentRequestsEmpty';
            empty.className = 'empty-state';
            empty.textContent = 'No tienes solicitudes pendientes.';
            paymentRequestsCard.appendChild(empty);
        }
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        modal.classList.remove('is-open');
    }

    recipientUsernameInput?.addEventListener('input', () => {
        if (recipientIdInput) {
            recipientIdInput.value = '';
        }
    });

    requestTargetUsernameInput?.addEventListener('input', () => {
        if (requestTargetIdInput) {
            requestTargetIdInput.value = '';
        }
    });

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-close-modal');
            closeModal(byId(modalId));
        });
    });

    [sendModal, receiveModal].forEach((modal) => {
        modal?.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        closeModal(sendModal);
        closeModal(receiveModal);
    });

    logoutButton?.addEventListener('click', async () => {
        if (!logoutUrl) {
            return;
        }

        try {
            const response = await fetch(logoutUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json().catch(() => ({}));
            window.location.assign(data.redirect ?? loginUrl);
        } catch (error) {
            console.error('Error logging out:', error);
        }
    });

    // Manejo del formulario de envío de dinero
    sendMoneyForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const recipientId = recipientIdInput?.value;
        const recipientUsername = recipientUsernameInput?.value?.trim() ?? '';
        const amount = byId('transferAmount')?.value;
        const numericAmount = parseFloat(amount);

        if ((!recipientId && !recipientUsername) || !amount) {
            showSendError('Por favor completa todos los campos.');
            return;
        }

        try {
            const response = await fetch('/api/transactions', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    recipientId: recipientId ? parseInt(recipientId, 10) : null,
                    recipientUsername,
                    transferAmount: numericAmount,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                showSendError(data.message || 'Error al enviar dinero.');
                return;
            }

            // Éxito: mostrar mensaje y limpiar formulario
            sendMoneyForm.reset();
            hideSendError();
            closeModal(sendModal);
            updateBalance(data.newBalance);
            prependTransaction(data.recipientName || recipientUsername, numericAmount, false);
        } catch (error) {
            console.error('Error en transacción:', error);
            showSendError('Error de conexión al enviar dinero.');
        }
    });

    requestPaymentForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const targetId = requestTargetIdInput?.value;
        const targetUsername = requestTargetUsernameInput?.value?.trim() ?? '';
        const amount = byId('requestAmount')?.value;
        const numericAmount = parseFloat(amount);

        if ((!targetId && !targetUsername) || !amount) {
            showReceiveError('Por favor completa todos los campos.');
            return;
        }

        try {
            const response = await fetch('/api/payment-requests', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    targetId: targetId ? parseInt(targetId, 10) : null,
                    targetUsername,
                    requestAmount: numericAmount,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                showReceiveError(data.message || 'Error al crear la solicitud.');
                return;
            }

            requestPaymentForm.reset();
            hideReceiveError();
            closeModal(receiveModal);
        } catch (error) {
            console.error('Error al crear solicitud:', error);
            showReceiveError('Error de conexión al crear la solicitud.');
        }
    });

    paymentRequestsCard?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-request-action]');
        if (!button) {
            return;
        }

        const action = button.getAttribute('data-request-action');
        const requestId = button.getAttribute('data-request-id');
        const requester = button.getAttribute('data-requester') ?? 'Usuario';
        const amount = Number(button.getAttribute('data-request-amount') || 0);

        if (!action || !requestId) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(`/api/payment-requests/${requestId}/respond`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ action }),
            });

            const data = await response.json();

            if (!response.ok) {
                showPaymentRequestError(data.message || 'No se pudo procesar la solicitud.');
                button.disabled = false;
                return;
            }

            removePaymentRequestRow(requestId);
            hidePaymentRequestError();

            if (action === 'accept') {
                updateBalance(data.newBalance);
                prependTransaction(requester, amount, false);
            }
        } catch (error) {
            console.error('Error al responder solicitud:', error);
            showPaymentRequestError('Error de conexión al responder solicitud.');
            button.disabled = false;
        }
    });

    function showSendError(message) {
        if (sendErrorElement) {
            sendErrorElement.textContent = message;
            sendErrorElement.classList.add('show');
        }
    }

    function hideSendError() {
        if (sendErrorElement) {
            sendErrorElement.textContent = '';
            sendErrorElement.classList.remove('show');
        }
    }

    function showReceiveError(message) {
        if (receiveErrorElement) {
            receiveErrorElement.textContent = message;
            receiveErrorElement.classList.add('show');
        }
    }

    function hideReceiveError() {
        if (receiveErrorElement) {
            receiveErrorElement.textContent = '';
            receiveErrorElement.classList.remove('show');
        }
    }

    function showPaymentRequestError(message) {
        if (paymentRequestErrorElement) {
            paymentRequestErrorElement.textContent = message;
            paymentRequestErrorElement.classList.add('show');
        }
    }

    function hidePaymentRequestError() {
        if (paymentRequestErrorElement) {
            paymentRequestErrorElement.textContent = '';
            paymentRequestErrorElement.classList.remove('show');
        }
    }

    // --- Live polling ---
    let isPolling = false;

    function getRenderedRequestIds() {
        return new Set(
            [...document.querySelectorAll('.request-row[data-request-id]')]
                .map((el) => String(el.dataset.requestId))
        );
    }

    function buildRequestRow(item) {
        const row = document.createElement('div');
        row.className = 'row-item request-row';
        row.dataset.requestId = item.id;
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        row.style.transition = 'opacity 0.4s ease, transform 0.4s ease';

        const left = document.createElement('div');
        const name = document.createElement('p');
        name.textContent = item.requester;
        const date = document.createElement('small');
        date.textContent = item.date;
        left.appendChild(name);
        left.appendChild(date);

        const right = document.createElement('div');
        right.className = 'right request-actions-wrap';

        const amountEl = document.createElement('p');
        amountEl.className = 'amount is-request';
        amountEl.textContent = item.amountLabel;

        const actions = document.createElement('div');
        actions.className = 'request-actions';

        const acceptBtn = document.createElement('button');
        acceptBtn.type = 'button';
        acceptBtn.className = 'request-btn is-accept';
        acceptBtn.dataset.requestAction = 'accept';
        acceptBtn.dataset.requestId = item.id;
        acceptBtn.dataset.requestAmount = item.amount;
        acceptBtn.dataset.requester = item.requester;
        acceptBtn.textContent = 'Aceptar';

        const rejectBtn = document.createElement('button');
        rejectBtn.type = 'button';
        rejectBtn.className = 'request-btn is-reject';
        rejectBtn.dataset.requestAction = 'reject';
        rejectBtn.dataset.requestId = item.id;
        rejectBtn.textContent = 'Rechazar';

        actions.appendChild(acceptBtn);
        actions.appendChild(rejectBtn);
        right.appendChild(amountEl);
        right.appendChild(actions);
        row.appendChild(left);
        row.appendChild(right);

        return row;
    }

    function syncPaymentRequests(items) {
        if (!paymentRequestsCard) return;

        const renderedIds = getRenderedRequestIds();
        const serverIds = new Set(items.map((r) => String(r.id)));

        // Insertar solicitudes nuevas que aún no están en el DOM
        const newItems = items.filter((r) => !renderedIds.has(String(r.id)));
        if (newItems.length > 0) {
            let list = byId('paymentRequestsList');
            const empty = byId('paymentRequestsEmpty');
            if (empty) empty.remove();

            if (!list) {
                list = document.createElement('div');
                list.id = 'paymentRequestsList';
                list.className = 'list-stack';
                paymentRequestsCard.appendChild(list);
            }

            newItems.forEach((item) => {
            const newRow = buildRequestRow(item);
            list.prepend(newRow);
            setTimeout(function() {
                newRow.style.opacity = '1';
                newRow.style.transform = 'translateY(0)';
            }, 10);
        });
        }

        // Eliminar filas que el servidor ya no devuelve (gestionadas por otro usuario)
        renderedIds.forEach((id) => {
            if (!serverIds.has(id)) {
                const row = paymentRequestsCard.querySelector(`.request-row[data-request-id="${id}"]`);
                if (row) row.remove();
            }
        });

        // Si la lista quedó vacía, mostrar estado vacío
        const remaining = paymentRequestsCard.querySelectorAll('.request-row');
        if (remaining.length === 0 && !byId('paymentRequestsEmpty')) {
            const empty = document.createElement('p');
            empty.id = 'paymentRequestsEmpty';
            empty.className = 'empty-state';
            empty.textContent = 'No tienes solicitudes pendientes.';
            paymentRequestsCard.appendChild(empty);
        }
    }

    async function pollDashboardState() {
        if (isPolling) return;
        isPolling = true;

        try {
            const response = await fetch('/api/dashboard-state', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!response.ok) return;

            const data = await response.json();
            updateBalance(data.balance);
            syncPaymentRequests(data.paymentRequestItems);
        } catch {
            // Silenciar errores de red — el usuario no necesita verlos
        } finally {
            isPolling = false;
        }
    }

    // Solo ejecutar una vez al cargar
    pollDashboardState();
})();
