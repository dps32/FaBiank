(() => {
    const $ = (id) => document.getElementById(id);
    const setupButton = $('twoFactorSetupButton');
    const codeInput = $('two_factor_code');
    const errorMessage = document.querySelector('.error-message');

    if (!setupButton || !codeInput) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const setupUrl = setupButton.dataset.setupUrl ?? '';
    const loginUrl = setupButton.dataset.loginUrl ?? '/login';

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

    codeInput.addEventListener('input', () => {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
    });

    setupButton.addEventListener('click', async () => {
        clearError();

        const code = codeInput.value.trim();

        if (!/^\d{6}$/.test(code)) {
            setError('El código debe tener 6 digitos.');
            return;
        }

        if (!setupUrl) {
            setError('No se ha configurado la URL de verificacion.');
            return;
        }

        try {
            setupButton.disabled = true;

            const response = await fetch(setupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ code }),
            });

            const raw = await response.text();
            const data = raw ? JSON.parse(raw) : {};

            if (!response.ok) {
                setError(data?.message ?? 'Código incorrecto.');
                return;
            }

            window.location.assign(data.redirect ?? loginUrl);
        } catch (error) {
            console.error('[2FA SETUP] Error de red o parseo', error);
            setError('No se pudo confirmar el código. Intentalo de nuevo.');
        } finally {
            setupButton.disabled = false;
        }
    });
})();
