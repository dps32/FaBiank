(() => {
    const $ = (id) => document.getElementById(id);
    const registerButton = $('registerButton');
    const errorMessage = document.querySelector('.error-message');

    if (!registerButton) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const registerUrl = registerButton.dataset.registerUrl ?? '';
    const loginUrl = registerButton.dataset.loginUrl ?? '/login';

    const value = (id) => $(id)?.value ?? '';

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

        errorMessage.textContent = '';
        errorMessage.classList.remove('show');
    };

    registerButton.addEventListener('click', async () => {
        clearError();
        console.log('[REGISTER] Inicio de registro');

        const payload = {
            name: value('name').trim(),
            username: value('username').trim(),
            password: value('password'),
            password_confirmation: value('password_confirmation'),
        };

        if (!payload.name || !payload.username || !payload.password || !payload.password_confirmation) {
            console.error('[REGISTER] Faltan campos obligatorios');
            setError('Completa todos los campos.');
            return;
        }

        if (payload.password !== payload.password_confirmation) {
            console.error('[REGISTER] La confirmacion de contraseña no coincide');
            setError('Las contraseñas no coinciden.');
            return;
        }

        if (payload.password.length < 8) {
            console.error('[REGISTER] La contraseña debe tener al menos 8 caracteres');
            setError('La contraseña es demasiado corta.');
            return;
        }

        if (!registerUrl) {
            console.error('[REGISTER] URL de registro no configurada');
            setError('No se ha configurado la URL de registro.');
            return;
        }

        console.log('[REGISTER] Payload', {
            name: payload.name,
            username: payload.username,
            password_length: payload.password.length,
            password_confirmation_length: payload.password_confirmation.length,
        });

        try {
            registerButton.disabled = true;

            const response = await fetch(registerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            console.log('[REGISTER] HTTP status', response.status);

            const raw = await response.text();
            let data = null;

            try {
                data = raw ? JSON.parse(raw) : {};
            } catch {
                console.error('[REGISTER] Respuesta no JSON', raw);
                return;
            }

            console.log('[REGISTER] Respuesta del servidor', data);

            if (!response.ok) {
                const errors = data?.errors ? Object.values(data.errors).flat() : [];
                console.error('[REGISTER] Error de validacion o servidor', errors);
                setError(errors[0] ?? data?.message ?? 'No se pudo completar el registro.');
                return;
            }

            console.log('[REGISTER] Registro completado correctamente');
            window.location.assign(data.redirect ?? loginUrl);
        } catch (error) {
            console.error('[REGISTER] Error de red o parseo', error);
            setError('No se pudo completar el registro. Intentalo de nuevo.');
        } finally {
            registerButton.disabled = false;
        }
    });
})();
