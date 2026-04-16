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

        console.log('[LOGIN] Inicio de login');
        console.log('[LOGIN] Payload', {
            username: payload.username,
            password_length: payload.password.length,
        });

        if (!payload.username || !payload.password) {
            console.error('[LOGIN] Faltan usuario o contraseña');
            setError('Introduce usuario y contraseña.');
            return;
        }

        if (!loginUrl) {
            console.error('[LOGIN] URL de login no configurada');
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

            console.log('[LOGIN] HTTP status', response.status);

            const raw = await response.text();
            let data = {};

            try {
                data = raw ? JSON.parse(raw) : {};
            } catch {
                console.error('[LOGIN] Respuesta no JSON', raw);
                return;
            }

            console.log('[LOGIN] Respuesta del servidor', data);

            if (!response.ok) {
                console.error('[LOGIN] Credenciales invalidas o error', data);
                setError(data?.message ?? 'Credenciales incorrectas.');
                return;
            }

            console.log('[LOGIN] Login correcto');
            window.location.assign(data.redirect ?? dashboardUrl);
        } catch (error) {
            console.error('[LOGIN] Error de red o parseo', error);
            setError('No se pudo iniciar sesion. Intentalo de nuevo.');
        } finally {
            loginButton.disabled = false;
        }
    });
})();
