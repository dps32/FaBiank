(() => {
    const logoutButton = document.getElementById('logoutButton');

    if (!logoutButton) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const logoutUrl = logoutButton.dataset.logoutUrl ?? '';
    const loginUrl = logoutButton.dataset.loginUrl ?? '/login';

    logoutButton.addEventListener('click', async () => {
        console.log('[LOGOUT] Inicio de logout');

        if (!logoutUrl) {
            console.error('[LOGOUT] URL de logout no configurada');
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

            console.log('[LOGOUT] HTTP status', response.status);

            if (!response.ok) {
                const raw = await response.text();
                console.error('[LOGOUT] Error al cerrar sesion', raw);
                return;
            }

            console.log('[LOGOUT] Logout correcto');
            window.location.assign(loginUrl);
        } catch (error) {
            console.error('[LOGOUT] Error de red o parseo', error);
        }
    });
})();
