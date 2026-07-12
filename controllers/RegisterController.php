<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json; charset=UTF-8');
verifyCsrfOrAbort();

$fullname = trim((string) ($_POST['fullname'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($fullname === '' || $username === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (mb_strlen($fullname) > 100 || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    echo json_encode(['success' => false, 'message' => 'Use a 3–50 character username containing letters, numbers, dots, underscores, or hyphens.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (strlen($password) < 12) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 12 characters.']);
    exit;
}

if (!hash_equals($password, $confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

try {
    $userModel = new User();
    if ($userModel->usernameExists($username) || $userModel->emailExists($email)) {
        echo json_encode(['success' => false, 'message' => 'That username or email address is already registered.']);
        exit;
    }

    $created = $userModel->createUser($fullname, $username, $email, password_hash($password, PASSWORD_DEFAULT), 'user');
    echo json_encode($created
        ? ['success' => true, 'message' => 'Account created successfully! You can now log in.', 'redirect' => BASE_URL . 'views/login.php']
        : ['success' => false, 'message' => 'Failed to create account. Please try again.']);
} catch (Throwable $exception) {
    error_log('Registration error: ' . $exception->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
