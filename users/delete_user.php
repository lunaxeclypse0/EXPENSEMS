<?php
require_once '../config/database.php';
$database = new Database();
$conn = $database->connect();

$id = $_GET['id'] ?? null;

if ($id) {
    $check = $conn->prepare("SELECT role FROM users WHERE id = :id");
    $check->execute(['id' => $id]);
    $user = $check->fetch();

    if ($user && $user['role'] === 'admin') {
        header("Location: index.php?error=cannot_delete_admin");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
}
header("Location: index.php");
exit;