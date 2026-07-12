document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');
    const togglePasswordIcon = document.getElementById('togglePassword');
    const loginBtn = document.getElementById('loginBtn');
    const loginSpinner = document.getElementById('loginSpinner');
    const loginCard = document.querySelector('.login-card');
    const rememberMe = document.getElementById('rememberMe');
    const toastEl = document.getElementById('loginToast');
    const toastBody = document.getElementById('toastBody');

    const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3500 }) : null;

    function clearErrors() {
        usernameInput?.classList.remove('is-invalid');
        passwordInput?.classList.remove('is-invalid');

        if (usernameError) usernameError.textContent = '';
        if (passwordError) passwordError.textContent = '';
    }

    function showToast(message, isSuccess = false) {
        if (!toast || !toastEl || !toastBody) return;

        toastBody.textContent = message;
        toastEl.classList.toggle('success', isSuccess);
        toast.show();
    }

    function setLoading(isLoading) {
        if (!loginBtn || !loginSpinner) return;

        loginBtn.disabled = isLoading;

        const btnText = loginBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.classList.toggle('d-none', isLoading);
        }

        loginSpinner.classList.toggle('d-none', !isLoading);
    }

    function shakeCard() {
        if (!loginCard) return;

        loginCard.classList.add('shake');
        setTimeout(() => loginCard.classList.remove('shake'), 500);
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();
        return {
            success: false,
            message: text || 'Unexpected server response.'
        };
    }

    async function postForm(url, formData) {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const data = await parseResponse(response);

        return { response, data };
    }

    if (togglePasswordIcon && passwordInput) {
        togglePasswordIcon.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            togglePasswordIcon.classList.toggle('fa-eye');
            togglePasswordIcon.classList.toggle('fa-eye-slash');
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearErrors();

            const username = usernameInput?.value.trim() || '';
            const password = passwordInput?.value || '';
            let hasError = false;

            if (username === '') {
                usernameInput?.classList.add('is-invalid');
                if (usernameError) usernameError.textContent = 'Username is required.';
                hasError = true;
            }

            if (password === '') {
                passwordInput?.classList.add('is-invalid');
                if (passwordError) passwordError.textContent = 'Password is required.';
                hasError = true;
            }

            if (hasError) {
                shakeCard();
                return;
            }

            setLoading(true);

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('remember_me', rememberMe?.checked ? '1' : '');
            formData.append('csrf_token', window.CSRF_TOKEN || '');

            try {
                const { response, data } = await postForm('../controllers/AuthController.php', formData);

                if (response.ok && data.success) {
                    showToast(data.message || 'Login successful.', true);

                    setTimeout(() => {
                        window.location.href = data.redirect || '../views/dashboard.php';
                    }, 800);
                    return;
                }

                setLoading(false);
                showToast(data.message || 'Login failed. Please try again.');
                shakeCard();
            } catch (error) {
                setLoading(false);
                showToast('Server error. Please try again.');
            }
        });
    }

    window.handleGoogleCredentialResponse = async function (googleResponse) {
        try {
            const formData = new FormData();
            formData.append('credential', googleResponse.credential);
            formData.append('csrf_token', window.CSRF_TOKEN || '');

            const { response, data } = await postForm('../controllers/GoogleAuthController.php', formData);

            if (response.ok && data.success) {
                showToast(data.message || 'Google sign-in successful.', true);

                setTimeout(() => {
                    window.location.href = data.redirect || '../views/dashboard.php';
                }, 800);
                return;
            }

            showToast(data.message || 'Google sign-in failed.');
        } catch (error) {
            showToast('Google sign-in failed. Please try again.');
        }
    };
});