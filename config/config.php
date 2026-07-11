<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
}

define('APP_NAME', 'Expense Management System');
define('BASE_URL', 'http://localhost/em-system/');
define('SESSION_TIMEOUT', 900);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');