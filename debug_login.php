<?php
require_once __DIR__ . '/config/database.php';

$username = 'admin';
$password = 'Admin@123';

try {
    $db = new Database();
    $conn = $db->connect();
    echo "Database connection: SUCCESS<br><br>";

    $stmt = $conn->prepare("SELECT id, fullname, username, password, role FROM users WHERE username = :username LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        echo "No user found with username: " . $username;
    } else {
        echo "User found:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Fullname: " . $user['fullname'] . "<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Password hash length: " . strlen($user['password']) . "<br>";
        echo "Password hash: " . $user['password'] . "<br><br>";

        if (password_verify($password, $user['password'])) {
            echo "Password MATCHES! Login should work.";
        } else {
            echo "Password DOES NOT MATCH.";
        }
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}