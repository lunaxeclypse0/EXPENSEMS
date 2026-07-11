<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db_connect.php';

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
}

header("Location: index.php");
exit();
?>