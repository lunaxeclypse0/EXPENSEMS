<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #070b13;
            --bg-secondary: #0c1423;
            --card-bg: rgba(10, 15, 26, 0.82);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #a8b0bf;
            --input-bg: rgba(255, 255, 255, 0.035);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-focus: rgba(155, 135, 255, 0.18);
            --accent: #9b87ff;
            --accent-hover: #8570f0;
            --shadow-lg: 0 24px 80px rgba(0, 0, 0, 0.45);
            --glass-blur: blur(20px);
        }

        [data-theme="light"] {
            --bg-main: #f4f6fb;
            --bg-secondary: #e9edf5;
            --card-bg: rgba(255, 255, 255, 0.84);
            --card-border: rgba(255, 255, 255, 0.72);
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --input-bg: rgba(255, 255, 255, 0.72);
            --input-border: rgba(148, 163, 184, 0.22);
            --input-focus: rgba(124, 92, 255, 0.22);
            --accent: #7c5cff;
            --accent-hover: #6a4de8;
            --shadow-lg: 0 20px 60px rgba(15, 23, 42, 0.14);
            --glass-blur: blur(18px);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            background:
                radial-gradient(circle at 15% 85%, rgba(255,255,255,0.10), transparent 26%),
                radial-gradient(circle at 82% 12%, rgba(255,255,255,0.12), transparent 24%),
                radial-gradient(circle at 50% 50%, rgba(124,92,255,0.06), transparent 34%),
                linear-gradient(135deg, var(--bg-main), var(--bg-secondary));
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
            transition: background 0.35s ease, color 0.35s ease;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            filter: blur(75px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.22;
        }

        body::before {
            left: -120px;
            bottom: -120px;
            background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 60%);
        }

        body::after {
            right: -90px;
            top: -90px;
            background: radial-gradient(circle, rgba(255,255,255,0.14), transparent 58%);
        }

        .theme-toggle-wrap {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 30;
        }

        .theme-toggle-btn {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            color: var(--text-primary);
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 28px rgba(0,0,0,0.14);
            transition: transform 0.2s ease, border-color 0.2s ease, background 0.3s ease;
        }

        .theme-toggle-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(155, 135, 255, 0.35);
        }

        .login-shell {
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 14px;
            position: relative;
            z-index: 1;
        }

        .login-wrapper {
            width: 100%;
            max-width: 418px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            box-shadow: var(--shadow-lg);
            border-radius: 28px;
            padding: 22px 20px 18px;
            position: relative;
            overflow: hidden;
            min-height: 560px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.04), transparent 22%),
                radial-gradient(circle at top, rgba(255,255,255,0.03), transparent 40%);
            pointer-events: none;
        }

        .brand-section,
        .step-indicator,
        .step-panel,
        .step-actions,
        .bottom-links {
            position: relative;
            z-index: 1;
        }

        .brand-section {
            text-align: center;
            margin-bottom: 12px;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            margin: 0 auto 10px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
            border: 1px solid var(--card-border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 10px 24px rgba(0,0,0,0.18);
            color: var(--text-primary);
            font-size: 17px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .brand-title span {
            color: var(--accent);
        }

        .brand-subtitle {
            margin: 4px 0 0;
            color: var(--text-secondary);
            font-size: 11px;
            line-height: 1.3;
        }

        .welcome-text {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            margin: 8px 0 4px;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }

        .welcome-sub {
            text-align: center;
            color: var(--text-secondary);
            font-size: 11px;
            margin-bottom: 10px;
            line-height: 1.35;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-bottom: 12px;
        }

        .step-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }

        .step-dot.active {
            width: 22px;
            background: var(--accent);
            border-color: var(--accent);
        }

        .step-panel {
            display: none;
        }

        .step-panel.active {
            display: block;
        }

        .input-group-custom {
            margin-bottom: 10px !important;
        }

        .form-label {
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap > i.fa-user,
        .input-icon-wrap > i.fa-lock,
        .input-icon-wrap > i.fa-envelope,
        .input-icon-wrap > i.fa-id-card {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 12px;
            pointer-events: none;
        }

        .form-control {
            height: 46px;
            border-radius: 15px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--text-primary);
            padding: 10px 40px 10px 40px;
            font-size: 13px;
            box-shadow: none !important;
            transition: border-color 0.2s ease, background 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.74;
        }

        .form-control:focus {
            border-color: var(--accent);
            background: var(--input-bg);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px var(--input-focus) !important;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 12px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--accent);
        }

        .invalid-feedback-custom {
            color: #f87171;
            font-size: 10.5px;
            margin-top: 4px;
            min-height: 11px;
            line-height: 1.2;
        }

        .step-actions {
            display: flex;
            gap: 8px;
            margin-top: 2px;
        }

        .btn-auth,
        .btn-login {
            height: 46px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.01em;
            transition: transform 0.18s ease, filter 0.2s ease, opacity 0.2s ease;
        }

        .btn-auth:hover,
        .btn-login:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        .btn-auth:active,
        .btn-login:active {
            transform: translateY(0);
        }

        .btn-secondary-auth {
            flex: 1;
            background: rgba(255,255,255,0.04);
            color: var(--text-primary);
            border: 1px solid var(--input-border);
        }

        .btn-login {
            flex: 1;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(240,240,240,0.96));
            color: #111827;
            box-shadow: 0 10px 24px rgba(255,255,255,0.05), 0 10px 26px rgba(0,0,0,0.18);
        }

        [data-theme="light"] .btn-login {
            background: linear-gradient(180deg, var(--accent), var(--accent-hover));
            color: #fff;
            box-shadow: 0 14px 30px rgba(124, 92, 255, 0.22);
        }

        .bottom-links {
            margin-top: 12px;
        }

        .forgot-link,
        .text-center.small {
            color: var(--text-secondary);
        }

        .forgot-link {
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 11px;
        }

        .forgot-link:hover {
            color: var(--accent);
        }

        .text-center.mt-3.mb-0.small {
            margin-top: 8px !important;
            margin-bottom: 0 !important;
            font-size: 11px !important;
            line-height: 1.35;
        }

        .footer-note {
            text-align: center;
            font-size: 10px;
            color: var(--text-secondary);
            margin: 8px 0 0;
            opacity: 0.8;
            line-height: 1.3;
        }

        .toast-container .toast {
            border-radius: 14px;
            overflow: hidden;
            background: #111827;
            color: #fff;
        }

        @media (max-width: 575px) {
            .theme-toggle-wrap {
                top: 10px;
                right: 10px;
            }

            .theme-toggle-btn {
                width: 38px;
                height: 38px;
                border-radius: 12px;
            }

            .login-shell {
                padding: 14px 10px;
            }

            .login-wrapper {
                max-width: 360px;
            }

            .login-card {
                border-radius: 24px;
                padding: 20px 16px 16px;
                min-height: 530px;
            }

            .brand-logo {
                width: 48px;
                height: 48px;
                font-size: 16px;
                margin-bottom: 9px;
            }

            .brand-title {
                font-size: 20px;
            }

            .brand-subtitle {
                font-size: 10.5px;
            }

            .welcome-text {
                font-size: 15px;
            }

            .welcome-sub {
                font-size: 10.5px;
            }

            .form-control,
            .btn-auth,
            .btn-login {
                height: 44px;
            }
        }

        @media (max-width: 390px) {
            .login-wrapper {
                max-width: 336px;
            }

            .login-card {
                padding: 18px 14px 14px;
                min-height: 510px;
            }

            .brand-subtitle {
                display: none;
            }

            .welcome-sub {
                margin-bottom: 8px;
            }

            .form-control {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="theme-toggle-wrap">
    <button class="theme-toggle-btn" id="themeToggle" type="button" aria-label="Toggle theme">
        <i class="fa-solid fa-gear"></i>
    </button>
</div>

<div class="login-shell">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-section">
                <div class="brand-logo">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h1 class="brand-title">Expense<span>MS</span></h1>
                <p class="brand-subtitle">Track. Manage. Report.</p>
            </div>

            <h2 class="welcome-text">Create account</h2>
            <p class="welcome-sub">Fill in your details to get started</p>

            <div class="step-indicator">
                <span class="step-dot active" id="dot1"></span>
                <span class="step-dot" id="dot2"></span>
            </div>

            <form id="registerForm" novalidate>
                <div class="step-panel active" id="step1">
                    <div class="mb-3 input-group-custom">
                        <label for="fullname" class="form-label">Full Name</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-id-card"></i>
                            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Enter your full name" autocomplete="name">
                        </div>
                        <div class="invalid-feedback-custom" id="fullnameError"></div>
                    </div>

                    <div class="mb-3 input-group-custom">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" autocomplete="username">
                        </div>
                        <div class="invalid-feedback-custom" id="usernameError"></div>
                    </div>

                    <div class="mb-3 input-group-custom">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autocomplete="email">
                        </div>
                        <div class="invalid-feedback-custom" id="emailError"></div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn btn-login w-100" id="nextStepBtn">Continue</button>
                    </div>
                </div>

                <div class="step-panel" id="step2">
                    <div class="mb-3 input-group-custom">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" autocomplete="new-password">
                            <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                        <div class="invalid-feedback-custom" id="passwordError"></div>
                    </div>

                    <div class="mb-3 input-group-custom">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" autocomplete="new-password">
                            <i class="fa-solid fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                        <div class="invalid-feedback-custom" id="confirmPasswordError"></div>
                    </div>

                    <div class="step-actions">
                        <button type="button" class="btn btn-auth btn-secondary-auth" id="prevStepBtn">Back</button>
                        <button type="submit" class="btn btn-login" id="registerBtn">
                            <span class="btn-text">Create Account</span>
                            <span class="spinner-border spinner-border-sm d-none" id="registerSpinner" role="status"></span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="bottom-links">
                <p class="text-center mt-3 mb-0 small">
                    Already have an account? <a href="login.php" class="forgot-link">Sign in here</a>
                </p>

                <p class="footer-note">&copy; <?php echo date('Y'); ?> Expense Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="registerToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const themeToggle = document.getElementById('themeToggle');
    const root = document.documentElement;
    let currentTheme = localStorage.getItem('theme') || 'dark';
    root.setAttribute('data-theme', currentTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', currentTheme);
            localStorage.setItem('theme', currentTheme);
        });
    }

    function bindPasswordToggle(toggleEl, inputEl) {
        if (!toggleEl || !inputEl) return;
        toggleEl.addEventListener('click', () => {
            const isPassword = inputEl.type === 'password';
            inputEl.type = isPassword ? 'text' : 'password';
            toggleEl.classList.toggle('fa-eye');
            toggleEl.classList.toggle('fa-eye-slash');
        });
    }

    bindPasswordToggle(document.getElementById('togglePassword'), document.getElementById('password'));
    bindPasswordToggle(document.getElementById('toggleConfirmPassword'), document.getElementById('confirm_password'));

    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const dot1 = document.getElementById('dot1');
    const dot2 = document.getElementById('dot2');
    const nextStepBtn = document.getElementById('nextStepBtn');
    const prevStepBtn = document.getElementById('prevStepBtn');

    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const registerSpinner = document.getElementById('registerSpinner');
    const toastElement = document.getElementById('registerToast');
    const toastBody = document.getElementById('toastBody');
    const registerToast = new bootstrap.Toast(toastElement, { delay: 3000 });

    const fullname = document.getElementById('fullname');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    const fullnameError = document.getElementById('fullnameError');
    const usernameError = document.getElementById('usernameError');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    function showToast(message, isSuccess = true) {
        toastBody.textContent = message;
        toastElement.style.background = isSuccess ? '#166534' : '#991b1b';
        registerToast.show();
    }

    function setLoadingState(isLoading) {
        if (isLoading) {
            registerBtn.disabled = true;
            registerSpinner.classList.remove('d-none');
        } else {
            registerBtn.disabled = false;
            registerSpinner.classList.add('d-none');
        }
    }

    function clearErrors() {
        fullnameError.textContent = '';
        usernameError.textContent = '';
        emailError.textContent = '';
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';
    }

    function validateStep1() {
        let isValid = true;
        fullnameError.textContent = '';
        usernameError.textContent = '';
        emailError.textContent = '';

        if (fullname.value.trim() === '') {
            fullnameError.textContent = 'Full name is required.';
            isValid = false;
        }

        if (username.value.trim() === '') {
            usernameError.textContent = 'Username is required.';
            isValid = false;
        } else if (username.value.trim().length < 3) {
            usernameError.textContent = 'Username must be at least 3 characters.';
            isValid = false;
        }

        if (email.value.trim() === '') {
            emailError.textContent = 'Email address is required.';
            isValid = false;
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email.value.trim())) {
                emailError.textContent = 'Enter a valid email address.';
                isValid = false;
            }
        }

        return isValid;
    }

    function validateStep2() {
        let isValid = true;
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';

        if (password.value === '') {
            passwordError.textContent = 'Password is required.';
            isValid = false;
        } else if (password.value.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters.';
            isValid = false;
        }

        if (confirmPassword.value === '') {
            confirmPasswordError.textContent = 'Please confirm your password.';
            isValid = false;
        } else if (password.value !== confirmPassword.value) {
            confirmPasswordError.textContent = 'Passwords do not match.';
            isValid = false;
        }

        return isValid;
    }

    function goToStep(step) {
        if (step === 1) {
            step1.classList.add('active');
            step2.classList.remove('active');
            dot1.classList.add('active');
            dot2.classList.remove('active');
        } else {
            step1.classList.remove('active');
            step2.classList.add('active');
            dot1.classList.remove('active');
            dot2.classList.add('active');
        }
    }

    nextStepBtn.addEventListener('click', () => {
        if (validateStep1()) {
            goToStep(2);
        } else {
            showToast('Please complete Step 1 correctly.', false);
        }
    });

    prevStepBtn.addEventListener('click', () => {
        goToStep(1);
    });

    registerForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearErrors();

        const step1Valid = validateStep1();
        const step2Valid = validateStep2();

        if (!step1Valid) {
            goToStep(1);
            showToast('Please fix the highlighted errors.', false);
            return;
        }

        if (!step2Valid) {
            goToStep(2);
            showToast('Please fix the highlighted errors.', false);
            return;
        }

        setLoadingState(true);

        try {
            const formData = new FormData(registerForm);

            const response = await fetch('../auth/register_process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'Registration successful!', true);
                registerForm.reset();
                goToStep(1);

                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1200);
            } else {
                if (result.errors) {
                    fullnameError.textContent = result.errors.fullname || '';
                    usernameError.textContent = result.errors.username || '';
                    emailError.textContent = result.errors.email || '';
                    passwordError.textContent = result.errors.password || '';
                    confirmPasswordError.textContent = result.errors.confirm_password || '';
                }

                if (result.errors && (result.errors.fullname || result.errors.username || result.errors.email)) {
                    goToStep(1);
                } else {
                    goToStep(2);
                }

                showToast(result.message || 'Registration failed.', false);
            }
        } catch (error) {
            showToast('Something went wrong. Please try again.', false);
        } finally {
            setLoadingState(false);
        }
    });
</script>
</body>
</html>