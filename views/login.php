<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$timeout = isset($_GET['timeout']) ? true : false;
$registered = isset($_GET['registered']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        :root {
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

        [data-theme="dark"] {
            --bg-main: #090c12;
            --bg-secondary: #0d1320;
            --card-bg: rgba(12, 16, 26, 0.80);
            --card-border: rgba(255, 255, 255, 0.07);
            --text-primary: #f8fafc;
            --text-secondary: #aab2c1;
            --input-bg: rgba(255, 255, 255, 0.04);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-focus: rgba(155, 135, 255, 0.18);
            --accent: #9b87ff;
            --accent-hover: #8570f0;
            --shadow-lg: 0 24px 80px rgba(0, 0, 0, 0.42);
            --glass-blur: blur(20px);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
            background:
                radial-gradient(circle at 12% 82%, rgba(255,255,255,0.12), transparent 24%),
                radial-gradient(circle at 84% 14%, rgba(255,255,255,0.16), transparent 22%),
                radial-gradient(circle at 50% 50%, rgba(124,92,255,0.06), transparent 32%),
                linear-gradient(135deg, var(--bg-main), var(--bg-secondary));
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            transition: background 0.35s ease, color 0.35s ease;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            filter: blur(78px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.26;
        }

        body::before {
            left: -120px;
            bottom: -120px;
            background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 58%);
        }

        body::after {
            right: -90px;
            top: -90px;
            background: radial-gradient(circle, rgba(255,255,255,0.18), transparent 58%);
        }

        .login-shell {
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            position: relative;
            z-index: 1;
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            box-shadow: var(--shadow-lg);
            border-radius: 30px;
            padding: 28px 24px 22px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.05), transparent 22%),
                radial-gradient(circle at top, rgba(255,255,255,0.05), transparent 40%);
            pointer-events: none;
        }

        .theme-toggle-wrap {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 20;
        }

        .theme-toggle-btn {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            color: var(--text-primary);
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 28px rgba(0,0,0,0.12);
            transition: transform 0.2s ease, border-color 0.2s ease, background 0.3s ease;
        }

        .theme-toggle-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(124, 92, 255, 0.35);
        }

        .brand-section {
            text-align: center;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            margin: 0 auto 12px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--card-border);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 8px 24px rgba(0,0,0,0.14);
            color: var(--text-primary);
            font-size: 19px;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }

        .brand-title span {
            color: var(--accent);
        }

        .brand-subtitle {
            margin: 5px 0 0;
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.35;
        }

        .welcome-text {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            margin: 10px 0 4px;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.15;
            position: relative;
            z-index: 1;
        }

        .welcome-sub {
            text-align: center;
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 16px;
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }

        .alert {
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 11.5px;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        [data-theme="dark"] .alert-warning {
            background: rgba(245, 158, 11, 0.12);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.15);
        }

        [data-theme="dark"] .alert-success {
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
            border-color: rgba(34, 197, 94, 0.14);
        }

        .input-group-custom {
            position: relative;
            z-index: 1;
            margin-bottom: 14px !important;
        }

        .form-label {
            color: var(--text-secondary);
            font-size: 11.5px;
            font-weight: 500;
            margin-bottom: 7px;
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
            font-size: 13px;
            pointer-events: none;
        }

        .form-control {
            height: 50px;
            border-radius: 16px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--text-primary);
            padding: 12px 42px 12px 42px;
            font-size: 13.5px;
            box-shadow: none !important;
            transition: border-color 0.2s ease, background 0.25s ease, box-shadow 0.25s ease;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
            opacity: 0.76;
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
            font-size: 13px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: var(--accent);
        }

        .invalid-feedback-custom {
            color: #f87171;
            font-size: 11px;
            margin-top: 5px;
            min-height: 15px;
            line-height: 1.25;
        }

        .form-check {
            display: flex;
            align-items: center;
            min-height: auto;
        }

        .form-check-input {
            width: 15px;
            height: 15px;
            margin-top: 0;
            background-color: transparent;
            border-color: var(--input-border);
            box-shadow: none !important;
        }

        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .form-check-label,
        .forgot-link,
        .text-center.small {
            color: var(--text-secondary);
        }

        .form-check-label {
            font-size: 11.5px;
            margin-left: 4px;
        }

        .forgot-link {
            text-decoration: none;
            transition: color 0.2s ease;
            font-size: 11.5px;
        }

        .forgot-link:hover {
            color: var(--accent);
        }

        .btn-login {
            height: 50px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(240,240,240,0.96));
            color: #111827;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.01em;
            box-shadow: 0 10px 24px rgba(255,255,255,0.06), 0 10px 26px rgba(0,0,0,0.18);
            transition: transform 0.18s ease, filter 0.2s ease, opacity 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        [data-theme="light"] .btn-login {
            background: linear-gradient(180deg, var(--accent), var(--accent-hover));
            color: #fff;
            box-shadow: 0 14px 30px rgba(124, 92, 255, 0.22);
        }

        .divider-or {
            position: relative;
            text-align: center;
            margin: 14px 0 12px;
            color: var(--text-secondary);
            font-size: 10px;
            letter-spacing: 0.12em;
            line-height: 1;
        }

        .divider-or::before,
        .divider-or::after {
            content: "";
            position: absolute;
            top: 50%;
            width: calc(50% - 22px);
            height: 1px;
            background: var(--input-border);
        }

        .divider-or::before {
            left: 0;
        }

        .divider-or::after {
            right: 0;
        }

        .g_id_signin {
            position: relative;
            z-index: 1;
            transform: scale(0.96);
            transform-origin: center top;
            margin-top: 0;
            margin-bottom: 0;
        }

        .text-center.mt-3.mb-0.small {
            margin-top: 12px !important;
            margin-bottom: 0 !important;
            font-size: 11.5px !important;
            line-height: 1.4;
        }

        .footer-note {
            text-align: center;
            font-size: 10.5px;
            color: var(--text-secondary);
            margin: 12px 0 0;
            opacity: 0.82;
            position: relative;
            z-index: 1;
            line-height: 1.35;
        }

        .toast-container .toast {
            border-radius: 14px;
            overflow: hidden;
            background: #111827;
            color: #fff;
        }

        [data-theme="light"] .toast-container .toast {
            background: #1f2937;
            color: #fff;
        }

        /* login actions row */
        .d-flex.justify-content-between.align-items-center.mb-4.mt-3 {
            margin-top: 9px !important;
            margin-bottom: 14px !important;
        }

        /* register: same design, slightly tighter vertical spacing only */
        .register-card .input-group-custom {
            margin-bottom: 10px !important;
        }

        .register-card .welcome-sub {
            margin-bottom: 12px;
        }

        .register-card .invalid-feedback-custom {
            min-height: 12px;
            margin-top: 4px;
        }

        .register-card .btn-login {
            margin-top: 2px !important;
        }

        .register-card .text-center.mt-3.mb-0.small {
            margin-top: 10px !important;
        }

        .register-card .footer-note {
            margin-top: 10px;
        }

        @media (max-width: 575px) {
            body::before,
            body::after {
                width: 280px;
                height: 280px;
                filter: blur(64px);
            }

            .theme-toggle-wrap {
                top: 10px;
                right: 10px;
            }

            .theme-toggle-btn {
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }

            .login-shell {
                padding: 16px 10px;
            }

            .login-wrapper {
                max-width: 360px;
            }

            .login-card {
                border-radius: 26px;
                padding: 22px 18px 18px;
            }

            .brand-section {
                margin-bottom: 14px;
            }

            .brand-logo {
                width: 50px;
                height: 50px;
                margin-bottom: 10px;
                font-size: 17px;
                border-radius: 15px;
            }

            .brand-title {
                font-size: 21px;
            }

            .brand-subtitle {
                font-size: 11px;
            }

            .welcome-text {
                font-size: 17px;
                margin: 8px 0 4px;
            }

            .welcome-sub {
                font-size: 11px;
                margin-bottom: 14px;
            }

            .form-label {
                font-size: 11px;
                margin-bottom: 6px;
            }

            .form-control {
                height: 48px;
                font-size: 16px;
                border-radius: 15px;
                padding: 12px 40px 12px 40px;
            }

            .toggle-password,
            .input-icon-wrap > i.fa-user,
            .input-icon-wrap > i.fa-lock,
            .input-icon-wrap > i.fa-envelope,
            .input-icon-wrap > i.fa-id-card {
                font-size: 12px;
            }

            .form-check-label,
            .forgot-link {
                font-size: 11px;
            }

            .btn-login {
                height: 48px;
                font-size: 13.5px;
            }

            .divider-or {
                margin: 12px 0 10px;
                font-size: 9.5px;
            }

            .g_id_signin {
                transform: scale(0.92);
                margin-bottom: -4px;
            }

            .text-center.mt-3.mb-0.small {
                margin-top: 10px !important;
                font-size: 11px !important;
            }

            .footer-note {
                margin-top: 10px;
                font-size: 10px;
            }

            .d-flex.justify-content-between.align-items-center.mb-4.mt-3 {
                margin-top: 8px !important;
                margin-bottom: 12px !important;
            }

            .register-card .input-group-custom {
                margin-bottom: 8px !important;
            }
        }

        @media (max-width: 390px) {
            .login-wrapper {
                max-width: 340px;
            }

            .login-card {
                padding: 20px 16px 16px;
            }

            .brand-title {
                font-size: 20px;
            }

            .brand-subtitle {
                display: none;
            }

            .welcome-sub {
                font-size: 10.5px;
                margin-bottom: 12px;
            }

            .form-control {
                height: 46px;
                font-size: 16px;
            }

            .btn-login {
                height: 46px;
                font-size: 13px;
            }

            .g_id_signin {
                transform: scale(0.88);
                margin-bottom: -10px;
            }
        }

        @media (max-height: 820px) {
            .login-shell {
                padding: 14px 10px;
            }

            .login-wrapper {
                max-width: 390px;
            }

            .login-card {
                padding: 22px 18px 16px;
                border-radius: 26px;
            }

            .brand-section {
                margin-bottom: 12px;
            }

            .brand-logo {
                width: 50px;
                height: 50px;
                margin-bottom: 9px;
                font-size: 17px;
            }

            .brand-title {
                font-size: 21px;
            }

            .brand-subtitle {
                font-size: 11px;
            }

            .welcome-text {
                font-size: 17px;
                margin-top: 8px;
            }

            .welcome-sub {
                font-size: 11px;
                margin-bottom: 12px;
            }

            .alert {
                font-size: 11px;
                margin-bottom: 10px;
            }

            .input-group-custom {
                margin-bottom: 12px !important;
            }

            .form-label {
                font-size: 11px;
                margin-bottom: 5px;
            }

            .form-control {
                height: 46px;
                font-size: 12.5px;
                border-radius: 15px;
            }

            .toggle-password,
            .input-icon-wrap > i.fa-user,
            .input-icon-wrap > i.fa-lock,
            .input-icon-wrap > i.fa-envelope,
            .input-icon-wrap > i.fa-id-card {
                font-size: 12px;
            }

            .form-check-label,
            .forgot-link {
                font-size: 11px;
            }

            .btn-login {
                height: 46px;
                font-size: 13px;
                border-radius: 15px;
            }

            .divider-or {
                margin: 12px 0 10px;
            }

            .g_id_signin {
                transform: scale(0.90);
                margin-top: -2px;
                margin-bottom: -8px;
            }

            .text-center.mt-3.mb-0.small {
                margin-top: 9px !important;
                font-size: 11px !important;
            }

            .footer-note {
                margin-top: 9px;
                font-size: 9.5px;
            }

            .d-flex.justify-content-between.align-items-center.mb-4.mt-3 {
                margin-top: 7px !important;
                margin-bottom: 12px !important;
            }

            .register-card .input-group-custom {
                margin-bottom: 8px !important;
            }

            .register-card .welcome-sub {
                margin-bottom: 10px;
            }

            .register-card .form-control {
                height: 44px;
            }

            .register-card .btn-login {
                height: 46px;
            }
        }
    </style>
</head>
<body>

<div class="theme-toggle-wrap">
    <button class="theme-toggle-btn" id="themeToggle" type="button" aria-label="Toggle theme">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
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

            <h2 class="welcome-text">Welcome back</h2>
            <p class="welcome-sub">Sign in to continue to your dashboard</p>

            <?php if ($timeout): ?>
                <div class="alert alert-warning py-2 small" id="timeoutAlert">
                    <i class="fa-solid fa-clock me-1"></i> Your session expired. Please log in again.
                </div>
            <?php endif; ?>

            <?php if ($registered): ?>
                <div class="alert alert-success py-2 small" id="registeredAlert">
                    <i class="fa-solid fa-circle-check me-1"></i> Account created! Please log in.
                </div>
            <?php endif; ?>

            <form id="loginForm" novalidate>
                <div class="mb-3 input-group-custom">
                    <label for="username" class="form-label">Username or Email</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username or email" autocomplete="username">
                    </div>
                    <div class="invalid-feedback-custom" id="usernameError"></div>
                </div>

                <div class="mb-2 input-group-custom">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                        <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                    <div class="invalid-feedback-custom" id="passwordError"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                        <label class="form-check-label small" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="forgot-link small">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-login w-100" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status"></span>
                </button>
            </form>

            <div class="divider-or">
                <span>OR</span>
            </div>

            <div id="g_id_onload"
                 data-client_id="112272491745-u4ant8kkv6r2mqsea73rr666ooatm3tq.apps.googleusercontent.com"
                 data-callback="handleGoogleCredentialResponse"
                 data-auto_prompt="false">
            </div>

            <div class="g_id_signin d-flex justify-content-center mb-2"
                 data-type="standard"
                 data-shape="pill"
                 data-theme="outline"
                 data-text="continue_with"
                 data-size="large"
                 data-width="320">
            </div>

            <p class="text-center mt-3 mb-0 small">
                Don't have an account? <a href="register.php" class="forgot-link">Register here</a>
            </p>

            <p class="footer-note">&copy; <?php echo date('Y'); ?> Expense Management System. All rights reserved.</p>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="loginToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const htmlEl = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    function applyTheme(theme) {
        htmlEl.setAttribute('data-theme', theme);
        localStorage.setItem('loginTheme', theme);
        themeIcon.className = theme === 'dark'
            ? 'fa-solid fa-sun'
            : 'fa-solid fa-moon';
    }

    const savedTheme = localStorage.getItem('loginTheme') || 'dark';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const currentTheme = htmlEl.getAttribute('data-theme') || 'dark';
        applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
    });

    togglePassword.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        togglePassword.classList.toggle('fa-eye');
        togglePassword.classList.toggle('fa-eye-slash');
    });
</script>
<script src="../assets/js/login.js"></script>
</body>
</html>