<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json; charset=UTF-8');

$action = (string) ($_POST['action'] ?? '');

if ($action === 'login') {
    handleLogin();
} elseif ($action === 'logout') {
    handleLogout();
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.',
    ]);
}

function handleLogin(): void
{
    global $conn;

    verifyCsrfOrAbort();

    $identifier = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

    if ($lockedUntil > time()) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many failed attempts. Please try again in a few minutes.',
        ]);
        return;
    }

    if ($identifier === '' || $password === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Username/email and password are required.',
        ]);
        return;
    }

    try {
        $userModel = new User();
        $user = $userModel->findByUsernameOrEmail($identifier);
        $passwordHash = (string) ($user['password'] ?? '');

        $validPassword = $user !== false
            && $passwordHash !== ''
            && password_verify($password, $passwordHash);

        // One-time migration for legacy plaintext passwords
        if ($user !== false && !$validPassword && $passwordHash !== '' && hash_equals($passwordHash, $password)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $userModel->updatePasswordHash((int) $user['id'], $newHash);
            $validPassword = true;
        }

        if (!$validPassword) {
            $_SESSION['login_attempts'] = $attempts + 1;

            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['login_locked_until'] = time() + 300;
            }

            echo json_encode([
                'success' => false,
                'message' => 'Invalid username/email or password.',
            ]);
            return;
        }

        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

        establishAuthenticatedSession($user);

        try {
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, type, link)
                VALUES (:user_id, :message, :type, :link)
            ");
            $notifStmt->execute([
                'user_id' => (int) $user['id'],
                'message' => 'Login successful. Welcome back!',
                'type' => 'login',
                'link' => '../views/dashboard.php',
            ]);
        } catch (Throwable $notificationException) {
            error_log('Login notification error: ' . $notificationException->getMessage());
        }

        if (!empty($_POST['remember_me'])) {
            issueRememberedLogin((int) $user['id']);
        } else {
            forgetRememberedLogin();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => BASE_URL . 'views/dashboard.php',
        ]);
    } catch (Throwable $exception) {
        error_log('Login error: ' . $exception->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Something went wrong. Please try again later.',
        ]);
    }
}

function handleLogout(): void
{
    requireLogin();
    verifyCsrfOrAbort();
    destroyCurrentSession();

    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully.',
        'redirect' => BASE_URL . 'views/login.php',
    ]);
}