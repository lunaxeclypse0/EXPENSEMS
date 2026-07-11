document.addEventListener('DOMContentLoaded', function () {
    const loginForm      = document.getElementById('loginForm');
    const usernameInput  = document.getElementById('username');
    const passwordInput  = document.getElementById('password');
    const usernameError  = document.getElementById('usernameError');
    const passwordError  = document.getElementById('passwordError');
    const togglePassword = document.getElementById('togglePassword');
    const loginBtn       = document.getElementById('loginBtn');
    const loginSpinner   = document.getElementById('loginSpinner');
    const loginCard      = document.querySelector('.login-card');
    const toastEl        = document.getElementById('loginToast');
    const toastBody      = document.getElementById('toastBody');
    const toast          = new bootstrap.Toast(toastEl, { delay: 3500 });

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    function clearErrors() {
        usernameInput.classList.remove('is-invalid');
        passwordInput.classList.remove('is-invalid');
        usernameError.textContent = '';
        passwordError.textContent = '';
    }

    function showToast(message, isSuccess = false) {
        toastBody.textContent = message;
        toastEl.classList.toggle('success', isSuccess);
        toast.show();
    }

    function setLoading(isLoading) {
        loginBtn.disabled = isLoading;
        loginBtn.querySelector('.btn-text').classList.toggle('d-none', isLoading);
        loginSpinner.classList.toggle('d-none', !isLoading);
    }

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const username = usernameInput.value.trim();
        const password  = passwordInput.value;
        let hasError    = false;

        if (username === '') {
            usernameInput.classList.add('is-invalid');
            usernameError.textContent = 'Username is required.';
            hasError = true;
        }

        if (password === '') {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'Password is required.';
            hasError = true;
        }

        if (hasError) {
            loginCard.classList.add('shake');
            setTimeout(() => loginCard.classList.remove('shake'), 500);
            return;
        }

        setLoading(true);

        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('username', username);
        formData.append('password', password);

        fetch('../controllers/AuthController.php', {
            method: 'POST',
            body: formData
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message, true);
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 800);
                } else {
                    setLoading(false);
                    showToast(data.message, false);
                    loginCard.classList.add('shake');
                    setTimeout(() => loginCard.classList.remove('shake'), 500);
                }
            })
            .catch(() => {
                setLoading(false);
                showToast('Server error. Please try again.', false);
            });
    });
});

/**
 * Google Sign-In Callback
 * Called automatically by Google's Identity Services library
 * once the user picks their Google account.
 */
function handleGoogleCredentialResponse(response) {
    const toastEl   = document.getElementById('loginToast');
    const toastBody = document.getElementById('toastBody');
    const toast     = new bootstrap.Toast(toastEl, { delay: 3500 });

    const formData = new FormData();
    formData.append('credential', response.credential);

    fetch('../controllers/GoogleAuthController.php', {
        method: 'POST',
        body: formData
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                toastBody.textContent = data.message;
                toastEl.classList.add('success');
                toast.show();
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 800);
            } else {
                toastBody.textContent = data.message;
                toastEl.classList.remove('success');
                toast.show();
            }
        })
        .catch(() => {
            toastBody.textContent = 'Google sign-in failed. Please try again.';
            toastEl.classList.remove('success');
            toast.show();
        });
}