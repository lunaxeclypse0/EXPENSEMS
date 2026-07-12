<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verifyCsrfOrAbort();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$id || $id === currentUserId()) {
    redirectTo('index.php?error=invalid_user');
}

$userStmt = $conn->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $id]);
$role = $userStmt->fetchColumn();

if ($role === false) {
    redirectTo('index.php?error=user_not_found');
}

if ($role === 'admin') {
    $adminCount = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount <= 1) {
        redirectTo('index.php?error=last_admin');
    }
}

$expenseCount = $conn->prepare('SELECT COUNT(*) FROM expenses WHERE user_id = :id');
$expenseCount->execute(['id' => $id]);

if ((int) $expenseCount->fetchColumn() > 0) {
    redirectTo('index.php?error=user_has_expenses');
}

$conn->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);

redirectTo('index.php?success=deleted');