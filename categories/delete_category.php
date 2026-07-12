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
    'options' => ['min_range' => 1]
]);

if (!$id) {
    redirectTo('index.php?error=invalid_category');
}

$category = $conn->prepare('SELECT name FROM categories WHERE id = :id LIMIT 1');
$category->execute(['id' => $id]);
$name = $category->fetchColumn();

if ($name === false) {
    redirectTo('index.php?error=category_not_found');
}

$inUse = $conn->prepare('SELECT COUNT(*) FROM expenses WHERE category = :name');
$inUse->execute(['name' => $name]);

if ((int) $inUse->fetchColumn() > 0) {
    redirectTo('index.php?error=category_in_use');
}

$conn->prepare('DELETE FROM categories WHERE id = :id')
    ->execute(['id' => $id]);

redirectTo('index.php?success=deleted');