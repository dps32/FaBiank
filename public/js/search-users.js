(() => {
    function byId(id) {
        return document.getElementById(id);
    }

    const searchButton = document.querySelector('#searchUserButton');
    const searchPanel = byId('searchPanel');
    const searchInput = byId('searchInput');
    const searchResults = byId('searchResults');
    const sendActionIconTemplate = byId('searchActionSendIconTemplate');
    const receiveActionIconTemplate = byId('searchActionReceiveIconTemplate');

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    const searchUrl = 'api/search-users';
    const sendActionIconMarkup = sendActionIconTemplate?.innerHTML ?? '';
    const receiveActionIconMarkup = receiveActionIconTemplate?.innerHTML ?? '';

    let searchTimeout;

    function isOpen() {
        return !!searchPanel?.classList.contains('is-open');
    }


    function openSearchPanel() {
        if (!searchPanel) return;
        searchPanel.classList.add('is-open');
        if (searchInput) searchInput.focus();
    }

    function closeSearchPanel() {
        if (!searchPanel) return;
        searchPanel.classList.remove('is-open');
        if (searchInput) searchInput.value = '';
        if (searchResults) searchResults.classList.remove('show');
    }

    function renderResults(users) {
        if (!searchResults) {
            return;
        }

        if (!users || users.length === 0) {
            searchResults.innerHTML = '<p class="search-empty">No se encontraron usuarios</p>';
            return;
        }

        searchResults.innerHTML = users.map((user) => `
            <div class="search-result-item" data-user-id="${user.id}" data-user-name="${escapeHtml(user.username)}">
                <div class="search-result-info">
                    <p class="search-result-name">${escapeHtml(user.name || user.username)}</p>
                    <p class="search-result-phone">@${escapeHtml(user.username)}</p>
                </div>
                <div class="search-result-actions">
                    <button type="button" class="search-result-action is-send" data-user-id="${user.id}" data-action="send">
                        <span>Enviar</span>
                        ${sendActionIconMarkup}
                    </button>
                    <button type="button" class="search-result-action is-receive" data-user-id="${user.id}" data-action="receive">
                        <span>Recibir</span>
                        ${receiveActionIconMarkup}
                    </button>
                </div>
            </div>
        `).join('');

        document.querySelectorAll('.search-result-action').forEach((button) => {
            button.addEventListener('click', () => {
                const userId = button.getAttribute('data-user-id');
                const action = button.getAttribute('data-action');
                const item = button.closest('.search-result-item');
                const userName = item ? item.getAttribute('data-user-name') : '';

                if (action === 'receive') {
                    handleReceiveMoney(userId, userName);
                    return;
                }

                handleSendMoney(userId, userName);
            });
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function performSearch(query) {
        if (!searchResults) {
            return;
        }


        try {
            const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (!response.ok) {
                searchResults.innerHTML = '<p class="search-empty">Error en la búsqueda</p>';
                return;
            }

            const data = await response.json();
            renderResults(data.users);
        } catch (error) {
            console.error('Error in search:', error);
            searchResults.innerHTML = '<p class="search-empty">Error de conexión</p>';
        }
    }

    function handleSendMoney(userId, userName) {
        closeSearchPanel();

        const recipientIdField = document.getElementById('recipientId');
        const recipientUsernameSpan = document.getElementById('recipientUsername');
        const recipientUsernameHidden = document.getElementById('recipientUsernameHidden');

        if (recipientIdField) recipientIdField.value = userId;
        if (recipientUsernameSpan) recipientUsernameSpan.textContent = userName || '';
        if (recipientUsernameHidden) recipientUsernameHidden.value = userName || '';

        const sendModal = document.getElementById('sendModal');
        if (sendModal) {
            sendModal.classList.add('is-open');

            const amountField = document.getElementById('transferAmount');
            if (amountField) {
                amountField.focus();
            }
        }
    }

    function handleReceiveMoney(userId, userName) {
        closeSearchPanel();

        const requestTargetIdField = document.getElementById('requestTargetId');
        const requestTargetUsernameSpan = document.getElementById('requestTargetUsername');
        const requestTargetUsernameHidden = document.getElementById('requestTargetUsernameHidden');

        if (requestTargetIdField) requestTargetIdField.value = userId;
        if (requestTargetUsernameSpan) requestTargetUsernameSpan.textContent = userName || '';
        if (requestTargetUsernameHidden) requestTargetUsernameHidden.value = userName || '';

        const receiveModal = document.getElementById('receiveModal');
        if (receiveModal) {
            receiveModal.classList.add('is-open');

            const amountField = document.getElementById('requestAmount');
            if (amountField) {
                amountField.focus();
            }
        }
    }

    searchButton?.addEventListener('click', () => {
        if (!searchPanel) {
            return;
        }

        if (!isOpen()) {
            openSearchPanel();
            return;
        }

        closeSearchPanel();
    });

    searchInput?.addEventListener('input', (e) => {
        // mostrar resultados solo cuando haya una búsqueda
        if (searchInput.value.trim().length === 0) {
            searchResults.classList.remove('show');
        } else {
            searchResults.classList.add('show');
        }

        const query = e.target.value;
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    document.addEventListener('click', (event) => {
        if (!searchPanel || !isOpen()) {
            return;
        }

        const clickInsidePanel = searchPanel.contains(event.target);
        const clickOnButton = searchButton?.contains(event.target);

        if (!clickInsidePanel && !clickOnButton) {
            closeSearchPanel();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            closeSearchPanel();
        }
    });
})();
