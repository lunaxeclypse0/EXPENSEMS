<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

verifyCsrfOrAbort();

if (!empty($_POST['all'])) {
    $stmt = $conn->prepare('
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = :user_id AND is_read = 0
    ');
    $stmt->execute([
        'user_id' => currentUserId(),
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read.',
    ]);
    exit;
}

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID.',
    ]);
    exit;
}

$stmt = $conn->prepare('
    UPDATE notifications
    SET is_read = 1
    WHERE id = :id AND user_id = :user_id
');
$stmt->execute([
    'id' => $id,
    'user_id' => currentUserId(),
]);

echo json_encode([
    'success' => true,
    'message' => 'Notification marked as read.',
]);