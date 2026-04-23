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

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') registerButton.click();
    });

    registerButton.addEventListener('click', async () => {
        clearError();

        const payload = {
            name: value('name').trim(),
            username: value('username').trim(),
            password: value('password'),
            password_confirmation: value('password_confirmation'),
        };

        if (!payload.name || !payload.username || !payload.password || !payload.password_confirmation) {
            setError('Completa todos los campos.');
            return;
        }

        if (payload.password !== payload.password_confirmation) {
            setError('Las contraseñas no coinciden.');
            return;
        }

        if (payload.password.length < 8) {
            setError('La contraseña es demasiado corta.');
            return;
        }

        if (!registerUrl) {
            setError('No se ha configurado la URL de registro.');
            return;
        }

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

            const raw = await response.text();
            let data = null;

            try {
                data = raw ? JSON.parse(raw) : {};
            } catch {
                return;
            }

            if (!response.ok) {
                const errors = data?.errors ? Object.values(data.errors).flat() : [];
                setError(errors[0] ?? data?.message ?? 'No se pudo completar el registro.');
                return;
            }

            window.location.assign(data.redirect ?? loginUrl);
        } catch (error) {
            setError('No se pudo completar el registro. Intentalo de nuevo.');
        } finally {
            registerButton.disabled = false;
        }
    });
})();
