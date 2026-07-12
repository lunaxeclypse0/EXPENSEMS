document.addEventListener('DOMContentLoaded', function () {
    const registerForm     = document.getElementById('registerForm');
    const fullnameInput    = document.getElementById('fullname');
    const usernameInput    = document.getElementById('username');
    const emailInput       = document.getElementById('email');
    const passwordInput    = document.getElementById('password');
    const confirmInput     = document.getElementById('confirm_password');

    const fullnameError    = document.getElementById('fullnameError');
    const usernameError    = document.getElementById('usernameError');
    const emailError       = document.getElementById('emailError');
    const passwordError    = document.getElementById('passwordError');
    const confirmError     = document.getElementById('confirmPasswordError');

    const togglePassword        = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

    const registerBtn      = document.getElementById('registerBtn');
    const registerSpinner  = document.getElementById('registerSpinner');
    const loginCard        = document.querySelector('.login-card');

    const toastEl   = document.getElementById('registerToast');
    const toastBody = document.getElementById('toastBody');
    const toast     = new bootstrap.Toast(toastEl, { delay: 3500 });

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    toggleConfirmPassword.addEventListener('click', function () {
        const isPassword = confirmInput.type === 'password';
        confirmInput.type = isPassword ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });

    function clearErrors() {
        [fullnameInput, usernameInput, emailInput, passwordInput, confirmInput].forEach(el => el.classList.remove('is-invalid'));
        [fullnameError, usernameError, emailError, passwordError, confirmError].forEach(el => el.textContent = '');
    }

    function showToast(message, isSuccess = false) {
        toastBody.textContent = message;
        toastEl.classList.toggle('success', isSuccess);
        toast.show();
    }

    function setLoading(isLoading) {
        registerBtn.disabled = isLoading;
        registerBtn.querySelector('.btn-text').classList.toggle('d-none', isLoading);
        registerSpinner.classList.toggle('d-none', !isLoading);
    }

    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const fullname = fullnameInput.value.trim();
        const username = usernameInput.value.trim();
        const email     = emailInput.value.trim();
        const password  = passwordInput.value;
        const confirm    = confirmInput.value;
        let hasError    = false;

        if (fullname === '') {
            fullnameInput.classList.add('is-invalid');
            fullnameError.textContent = 'Full name is required.';
            hasError = true;
        }

        if (username === '') {
            usernameInput.classList.add('is-invalid');
            usernameError.textContent = 'Username is required.';
            hasError = true;
        }

        if (email === '' || !email.includes('@')) {
            emailInput.classList.add('is-invalid');
            emailError.textContent = 'Valid email is required.';
            hasError = true;
        }

        if (password.length < 12) {
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = 'Password must be at least 12 characters.';
            hasError = true;
        }

        if (confirm !== password) {
            confirmInput.classList.add('is-invalid');
            confirmError.textContent = 'Passwords do not match.';
            hasError = true;
        }

        if (hasError) {
            loginCard.classList.add('shake');
            setTimeout(() => loginCard.classList.remove('shake'), 500);
            return;
        }

        setLoading(true);

        const formData = new FormData();
        formData.append('fullname', fullname);
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirm);

        fetch('../controllers/RegisterController.php', {
            method: 'POST',
            body: formData
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    showToast(data.message, true);
                    setTimeout(() => {
                        window.location.href = 'login.php?registered=1';
                    }, 1000);
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
