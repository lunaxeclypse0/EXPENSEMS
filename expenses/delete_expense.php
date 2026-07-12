<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verifyCsrfOrAbort();

$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    redirectTo('index.php?error=invalid_expense');
}

$isAdmin = isAdmin();
$scope = $isAdmin ? '' : ' AND user_id = :user_id';

$sql = 'DELETE FROM expenses WHERE id = :id' . $scope;
$stmt = $conn->prepare($sql);

$params = ['id' => $id];

if (!$isAdmin) {
    $params['user_id'] = currentUserId();
}

$stmt->execute($params);

redirectTo(
    'index.php?' . ($stmt->rowCount() > 0 ? 'success=deleted' : 'error=expense_not_found')
);