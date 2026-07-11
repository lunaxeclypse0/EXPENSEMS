<?php
/**
 * Auth Middleware
 * Protects pages that require an authenticated session
 * Include this file at the very TOP of any protected page (e.g., dashboard.php)
 */

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is logged in.
 * If not, redirect to login page.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'views/login.php');
        exit();
    }

    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'views/login.php?timeout=1');
        exit();
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Optional: restrict page to a specific role (e.g., 'admin')
 */
function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die('Access denied. You do not have permission to view this page.');
    }
}

requireLogin();