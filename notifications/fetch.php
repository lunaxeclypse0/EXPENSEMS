<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$stmt = $conn->prepare('
    SELECT id, message, type, link, is_read, created_at
    FROM notifications
    WHERE user_id = :user_id
    ORDER BY created_at DESC, id DESC
    LIMIT 10
');
$stmt->execute([
    'user_id' => currentUserId(),
]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread = 0;
foreach ($notifications as $notification) {
    if ((int) ($notification['is_read'] ?? 0) === 0) {
        $unread++;
    }
}

echo json_encode([
    'notifications' => $notifications,
    'unread' => $unread,
], JSON_UNESCAPED_UNICODE);