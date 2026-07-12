<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function isHttpsRequest(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function startSecureSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_name('em_system_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectTo(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function currentUserId(): ?int
{
    $id = $_SESSION['user_id'] ?? null;

    return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
        ? (int) $id
        : null;
}

function currentUserRole(): string
{
    return strtolower(trim((string) ($_SESSION['role'] ?? '')));
}

function establishAuthenticatedSession(array $user): void
{
    startSecureSession();
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['fullname'] = (string) ($user['fullname'] ?? '');
    $_SESSION['username'] = (string) ($user['username'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $_SESSION['role'] = strtolower(trim((string) ($user['role'] ?? 'user')));
    $_SESSION['last_activity'] = time();
}

function rememberCookieName(): string
{
    return 'em_system_remember';
}

function forgetRememberedLogin(): void
{
    $cookie = $_COOKIE[rememberCookieName()] ?? '';

    if (is_string($cookie) && preg_match('/^(\d+):([a-f0-9]{64})$/', $cookie, $matches)) {
        try {
            require_once __DIR__ . '/database.php';
            $pdo = (new Database())->connect();
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id AND token_hash = :token_hash')
                ->execute([
                    'user_id' => (int) $matches[1],
                    'token_hash' => hash('sha256', $matches[2]),
                ]);
        } catch (Throwable $exception) {
            error_log('Unable to remove remember-me token: ' . $exception->getMessage());
        }
    }

    setcookie(rememberCookieName(), '', [
        'expires' => time() - 42000,
        'path' => '/',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    unset($_COOKIE[rememberCookieName()]);
}

function issueRememberedLogin(int $userId): void
{
    require_once __DIR__ . '/database.php';

    $token = bin2hex(random_bytes(32));
    $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

    $pdo = (new Database())->connect();
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    $pdo->prepare('INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)')
        ->execute([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ]);

    setcookie(rememberCookieName(), $userId . ':' . $token, [
        'expires' => strtotime($expiresAt),
        'path' => '/',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function restoreRememberedLogin(): bool
{
    if (currentUserId() !== null) {
        return true;
    }

    $cookie = $_COOKIE[rememberCookieName()] ?? '';
    if (!is_string($cookie) || !preg_match('/^(\d+):([a-f0-9]{64})$/', $cookie, $matches)) {
        return false;
    }

    try {
        require_once __DIR__ . '/database.php';
        $pdo = (new Database())->connect();

        $stmt = $pdo->prepare('
            SELECT u.id, u.fullname, u.username, u.email, u.role
            FROM remember_tokens AS rt
            INNER JOIN users AS u ON u.id = rt.user_id
            WHERE rt.user_id = :user_id
              AND rt.token_hash = :token_hash
              AND rt.expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute([
            'user_id' => (int) $matches[1],
            'token_hash' => hash('sha256', $matches[2]),
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            forgetRememberedLogin();
            return false;
        }

        establishAuthenticatedSession($user);
        issueRememberedLogin((int) $user['id']);
        return true;
    } catch (Throwable $exception) {
        error_log('Unable to restore remembered login: ' . $exception->getMessage());
        return false;
    }
}

function destroyCurrentSession(): void
{
    startSecureSession();
    forgetRememberedLogin();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}

function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function isUser(): bool
{
    return currentUserRole() === 'user';
}

function hasRole(string ...$roles): bool
{
    $currentRole = currentUserRole();
    $normalizedRoles = array_map(
        static fn(string $role): string => strtolower(trim($role)),
        $roles
    );

    return in_array($currentRole, $normalizedRoles, true);
}

function requireLogin(): void
{
    startSecureSession();

    if (currentUserId() === null && !restoreRememberedLogin()) {
        redirectTo(BASE_URL . 'views/login.php');
    }

    $lastActivity = $_SESSION['last_activity'] ?? null;
    if (!is_int($lastActivity) || time() - $lastActivity > SESSION_TIMEOUT) {
        destroyCurrentSession();
        redirectTo(BASE_URL . 'views/login.php?timeout=1');
    }

    $_SESSION['last_activity'] = time();
}

function denyAccess(): never
{
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Unauthorized</title></head><body><h1>Unauthorized</h1><p>You do not have permission to access this page.</p></body></html>';
    exit;
}

function requireRole(string ...$roles): void
{
    requireLogin();

    if (!hasRole(...$roles)) {
        denyAccess();
    }
}

function requireAdmin(): void
{
    requireRole('admin');
}

function requireUser(): void
{
    requireRole('user');
}

function requireUserOrAdmin(): void
{
    requireRole('user', 'admin');
}

function csrfToken(): string
{
    startSecureSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function requestCsrfToken(): string
{
    return (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

function verifyCsrfOrAbort(): void
{
    $knownToken = $_SESSION['csrf_token'] ?? '';
    $submittedToken = requestCsrfToken();

    if (!is_string($knownToken) || $knownToken === '' || !hash_equals($knownToken, $submittedToken)) {
        http_response_code(419);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Your form has expired. Please refresh the page and try again.');
    }
}

function validDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

/** @return array{expense_date: string, category: string, description: string, amount: string, payment_method: string, remarks: string} */
function expenseInput(array $input): array
{
    return [
        'expense_date' => trim((string) ($input['expense_date'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'amount' => trim((string) ($input['amount'] ?? '')),
        'payment_method' => trim((string) ($input['payment_method'] ?? '')),
        'remarks' => trim((string) ($input['remarks'] ?? '')),
    ];
}

/** @return list<string> */
function validateExpenseInput(PDO $connection, array $expense): array
{
    $errors = [];

    if (!validDate($expense['expense_date'])) {
        $errors[] = 'Expense date is required and must be a valid date.';
    }

    if ($expense['category'] === '' || mb_strlen($expense['category']) > 100) {
        $errors[] = 'Please choose a valid category.';
    } else {
        $categoryStatement = $connection->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $categoryStatement->execute(['name' => $expense['category']]);

        if (!$categoryStatement->fetch()) {
            $errors[] = 'The selected category is no longer available.';
        }
    }

    if ($expense['description'] === '' || mb_strlen($expense['description']) > 1000) {
        $errors[] = 'Description is required and must not exceed 1,000 characters.';
    }

    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $expense['amount']) || (float) $expense['amount'] <= 0) {
        $errors[] = 'Amount must be a positive number with up to two decimal places.';
    }

    if ($expense['payment_method'] === '' || mb_strlen($expense['payment_method']) > 50) {
        $errors[] = 'Payment method is required and must not exceed 50 characters.';
    }

    if (mb_strlen($expense['remarks']) > 1000) {
        $errors[] = 'Remarks must not exceed 1,000 characters.';
    }

    return $errors;
}

startSecureSession();