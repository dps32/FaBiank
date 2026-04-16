(() => {
    function byId(id) {
        return document.getElementById(id);
    }

    const logoutButton = document.getElementById('logoutButton');
    const openSendModalButton = byId('openSendModal');
    const openReceiveModalButton = byId('openReceiveModal');
    const sendModal = byId('sendModal');
    const receiveModal = byId('receiveModal');
    const sendMoneyForm = byId('sendMoneyForm');
    const recipientUsernameInput = byId('recipientUsername');
    const recipientIdInput = byId('recipientId');
    const sendErrorElement = byId('sendError');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    const logoutUrl = logoutButton?.dataset.logoutUrl ?? '';
    const loginUrl = logoutButton?.dataset.loginUrl ?? '/login';

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

    openSendModalButton?.addEventListener('click', () => {
        if (!sendModal) {
            console.error('sendModal no encontrado');
            return;
        }
        openModal(sendModal);
    });

    openReceiveModalButton?.addEventListener('click', () => {
        if (!receiveModal) {
            console.error('receiveModal no encontrado');
            return;
        }
        openModal(receiveModal);
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
                    transferAmount: parseFloat(amount),
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

            // Recargar dashboard para ver cambios
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } catch (error) {
            console.error('Error en transacción:', error);
            showSendError('Error de conexión al enviar dinero.');
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
})();
