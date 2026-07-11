<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->connect();

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
}

if (isset($_GET['all'])) {
    $conn->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);