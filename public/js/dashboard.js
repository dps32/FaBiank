(() => {
    function byId(id) {
        return document.getElementById(id);
    }

    const logoutButton = document.getElementById('logoutButton');
    const openSendModalButton = byId('openSendModal');
    const openReceiveModalButton = byId('openReceiveModal');
    const sendModal = byId('sendModal');
    const receiveModal = byId('receiveModal');

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

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    openSendModalButton?.addEventListener('click', () => openModal(sendModal));
    openReceiveModalButton?.addEventListener('click', () => openModal(receiveModal));

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

            window.location.assign(loginUrl);
        } catch (error) {
            console.error('Error logging out:', error);
        }
    });
})();
