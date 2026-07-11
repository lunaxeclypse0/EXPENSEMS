<?php
/**
 * Register Controller
 * Handles new user registration via AJAX
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$fullname = trim($_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Basic validation
if ($fullname === '' || $username === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit();
}

if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}

// Sanitize
$fullname = htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$email    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

try {
    $userModel = new User();

    if ($userModel->usernameExists($username)) {
        echo json_encode(['success' => false, 'message' => 'Username already taken.']);
        exit();
    }

    if ($userModel->emailExists($email)) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $created = $userModel->createUser($fullname, $username, $email, $hashedPassword, 'staff');

    if ($created) {
        echo json_encode([
            'success'  => true,
            'message'  => 'Account created successfully! You can now log in.',
            'redirect' => BASE_URL . 'views/login.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account. Please try again.']);
    }
} catch (Throwable $e) {
    error_log('Register Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}