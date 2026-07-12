<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json; charset=UTF-8');

verifyCsrfOrAbort();

$clientId = (string) (getenv('GOOGLE_CLIENT_ID') ?: '112272491745-u4ant8kkv6r2mqsea73rr666ooatm3tq.apps.googleusercontent.com');
$credential = (string) ($_POST['credential'] ?? '');

if ($credential === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No Google credential was received.',
    ]);
    exit;
}

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents(
        'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential),
        false,
        $context
    );

    $payload = is_string($response) ? json_decode($response, true) : null;

    $validIssuer = in_array($payload['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
    $notExpired = isset($payload['exp']) && ctype_digit((string) $payload['exp']) && (int) $payload['exp'] >= time();
    $verifiedEmail = ($payload['email_verified'] ?? false) === true || ($payload['email_verified'] ?? '') === 'true';

    if (
        !is_array($payload)
        || ($payload['aud'] ?? '') !== $clientId
        || !$validIssuer
        || !$notExpired
        || !$verifiedEmail
        || empty($payload['email'])
        || empty($payload['sub'])
    ) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Google could not verify this sign-in.',
        ]);
        exit;
    }

    $googleId = (string) $payload['sub'];
    $email = strtolower((string) $payload['email']);
    $fullname = trim((string) ($payload['name'] ?? $email));

    $userModel = new User();
    $user = $userModel->findByGoogleId($googleId);

    if (!$user) {
        $existing = $userModel->findByEmail($email);
        if ($existing) {
            $userModel->linkGoogleId((int) $existing['id'], $googleId);
            $user = $userModel->findByGoogleId($googleId);
        }
    }

    if (!$user) {
        $baseUsername = preg_replace('/[^a-z0-9_.-]/', '', strtolower(strtok($email, '@'))) ?: 'googleuser';

        do {
            $username = substr($baseUsername, 0, 42) . random_int(10000000, 99999999);
        } while ($userModel->usernameExists($username));

        $userModel->createGoogleUser($fullname, $username, $email, $googleId, 'user');
        $user = $userModel->findByGoogleId($googleId);
    }

    if (!$user) {
        throw new RuntimeException('Google user creation did not return an account.');
    }

    establishAuthenticatedSession($user);

    try {
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, link)
            VALUES (:user_id, :message, :type, :link)
        ");
        $notifStmt->execute([
            'user_id' => (int) $user['id'],
            'message' => 'Google login successful. Welcome back!',
            'type' => 'login',
            'link' => '../views/dashboard.php',
        ]);
    } catch (Throwable $notificationException) {
        error_log('Google login notification error: ' . $notificationException->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Google login successful.',
        'redirect' => BASE_URL . 'views/dashboard.php',
    ]);
} catch (Throwable $exception) {
    error_log('Google authentication error: ' . $exception->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Google sign-in is temporarily unavailable.',
    ]);
}