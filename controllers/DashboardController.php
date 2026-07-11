<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../expenses/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../views/login.php');
    exit();
}

$expenseModel = new Expense();

$totalToday     = $expenseModel->getTotalToday();
$totalThisMonth = $expenseModel->getTotalThisMonth();
$totalOverall   = $expenseModel->getTotalOverall();
$recentExpenses = $expenseModel->getRecent(5);

$chartLabels = [];
$chartData = [];

$stmt = $conn->prepare("
    SELECT DATE(expense_date) as day, SUM(amount) as total
    FROM expenses
    WHERE expense_date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(expense_date)
    ORDER BY day ASC
");
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $chartLabels[] = date('M d', strtotime($row['day']));
    $chartData[] = (float)$row['total'];
}