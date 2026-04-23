(() => {
    const $ = (id) => document.getElementById(id);
    const loginButton = $('loginButton');
    const errorMessage = document.querySelector('.error-message');

    if (!loginButton) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const loginUrl = loginButton.dataset.loginUrl ?? '';
    const dashboardUrl = loginButton.dataset.dashboardUrl ?? '/dashboard';

    const setError = (message) => {
        if (!errorMessage) {
            return;
        }

        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    };

    const clearError = () => {
        if (!errorMessage) {
            return;
        }

        errorMessage.classList.remove('show');
    };

    loginButton.addEventListener('click', async () => {
        clearError();

        const payload = {
            username: $('username')?.value.trim() ?? '',
            password: $('password')?.value ?? '',
        };

        if (!payload.username || !payload.password) {
            setError('Introduce usuario y contraseña.');
            return;
        }

        if (!loginUrl) {
            setError('No se ha configurado la URL de login.');
            return;
        }

        try {
            loginButton.disabled = true;

            const response = await fetch(loginUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const raw = await response.text();
            let data = {};

            try {
                data = raw ? JSON.parse(raw) : {};
            } catch {
                return;
            }

            if (!response.ok) {
                setError(data?.message ?? 'Credenciales incorrectas.');
                return;
            }

            window.location.assign(data.redirect ?? dashboardUrl);
        } catch (error) {
            setError('No se pudo iniciar sesion. Intentalo de nuevo.');
        } finally {
            loginButton.disabled = false;
        }
    });
})();
