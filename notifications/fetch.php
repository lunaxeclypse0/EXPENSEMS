<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->connect();

$stmt = $conn->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
foreach ($notifications as $n) {
    if ((int)$n['is_read'] === 0) {
        $unreadCount++;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'notifications' => $notifications,
    'unread' => $unreadCount
]);