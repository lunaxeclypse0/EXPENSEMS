<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/User.php';

$username = 'admin';
$plainPassword = 'admin@123';

$userModel = new User();
$user = $userModel->findByUsernameOrEmail($username);

if (!$user) {
    die("<h3>PROBLEM: Walang user na nahanap gamit ang '$username'. Baka mali ang findByUsernameOrEmail() query.</h3>");
}

echo "<h3>User found:</h3>";
echo "<p>Username: " . htmlspecialchars($user['username']) . "</p>";
echo "<p>Stored hash: " . htmlspecialchars($user['password']) . "</p>";
echo "<p>Hash length: " . strlen($user['password']) . " characters</p>";

$verifyResult = password_verify($plainPassword, $user['password']);

echo "<h3>password_verify result: " . ($verifyResult ? "TRUE (dapat gumana ang login)" : "FALSE (ito ang sanhi ng error)") . "</h3>";