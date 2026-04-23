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

        const userNode = document.createElement('p');
        userNode.textContent = (counterparty || 'Usuario').trim() || 'Usuario';

        const amountNode = document.createElement('p');
        amountNode.className = `amount ${incoming ? 'is-positive' : 'is-negative'}`;
        amountNode.textContent = `${incoming ? '+' : '-'} $${formatMoney(amount)}`;

        row.appendChild(userNode);
        row.appendChild(amountNode);
        list.prepend(row);

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
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        if (document.activeElement && modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
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
            prependTransaction(recipientUsername, numericAmount, false);
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
})();
