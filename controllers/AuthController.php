<?php
/**
 * Auth Controller
 * Handles login and logout logic
 * Receives AJAX POST requests from login.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function handleLogin(): void
{
    $identifier = trim($_POST['username'] ?? ''); // can be username OR email
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Username/Email and password are required.'
        ]);
        return;
    }

    $identifier = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');

    try {
        $userModel = new User();
        $user = $userModel->findByUsernameOrEmail($identifier);

        if (!$user || $password !== $user['password']) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username/email or password.'
            ]);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['fullname']      = $user['fullname'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['email']         = $user['email'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();

        echo json_encode([
            'success'  => true,
            'message'  => 'Login successful.',
            'redirect' => BASE_URL . 'views/dashboard.php'
        ]);
    } catch (Throwable $e) {
        error_log('Login Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Something went wrong. Please try again later.'
        ]);
    }
}

function handleLogout(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }

    session_destroy();

    echo json_encode([
        'success'  => true,
        'message'  => 'Logged out successfully.',
        'redirect' => BASE_URL . 'views/login.php'
    ]);
}