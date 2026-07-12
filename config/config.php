<?php
declare(strict_types=1);

defined('APP_NAME') || define('APP_NAME', 'Expense Management System');
defined('BASE_URL') || define('BASE_URL', rtrim((string) (getenv('APP_URL') ?: 'http://localhost/em-system'), '/') . '/');
defined('SESSION_TIMEOUT') || define('SESSION_TIMEOUT', 900);

date_default_timezone_set((string) (getenv('APP_TIMEZONE') ?: 'Asia/Manila'));

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', (getenv('APP_ENV') === 'development') ? '1' : '0');
