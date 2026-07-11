<?php
/**
 * Google Auth Controller
 * Verifies Google ID token and logs in / registers the user
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

$GOOGLE_CLIENT_ID = '112272491745-u4ant8kkv6r2mqsea73rr666ooatm3tq.apps.googleusercontent.com';

$credential = $_POST['credential'] ?? '';

if ($credential === '') {
    echo json_encode(['success' => false, 'message' => 'No credential received.']);
    exit();
}

try {
    $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
    $response = file_get_contents($verifyUrl);

    if ($response === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to verify Google token.']);
        exit();
    }

    $payload = json_decode($response, true);

    if (!isset($payload['aud']) || $payload['aud'] !== $GOOGLE_CLIENT_ID) {
        echo json_encode(['success' => false, 'message' => 'Invalid token audience.']);
        exit();
    }

    if (!isset($payload['email']) || !isset($payload['sub'])) {
        echo json_encode(['success' => false, 'message' => 'Incomplete Google profile data.']);
        exit();
    }

    $googleId = $payload['sub'];
    $email    = $payload['email'];
    $fullname = $payload['name'] ?? $email;

    $userModel = new User();

    $user = $userModel->findByGoogleId($googleId);

    if (!$user) {
        $existing = $userModel->findByEmail($email);

        if ($existing) {
            $userModel->linkGoogleId($existing['id'], $googleId);
            $user = $existing;
        }
    }

    if (!$user) {
        $username = strtolower(explode('@', $email)[0]) . rand(100, 999);
        $userModel->createGoogleUser($fullname, $username, $email, $googleId, 'staff');
        $user = $userModel->findByGoogleId($googleId);
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
        'message'  => 'Google login successful.',
        'redirect' => BASE_URL . 'views/dashboard.php'
    ]);
} catch (Throwable $e) {
    error_log('Google Auth Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Something went wrong during Google login.']);
}