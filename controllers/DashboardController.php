<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../models/Expense.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$userId = currentUserId();
$expenseOwnerId = isAdmin() ? null : $userId;

$totalToday = 0;
$totalThisMonth = 0;
$totalOverall = 0;
$recentExpenses = [];
$chartLabels = [];
$chartData = [];
$categoryLabels = [];
$categoryData = [];

try {
    $expenseModel = new Expense();

    if (method_exists($expenseModel, 'getDashboardTotals')) {
        $totals = $expenseModel->getDashboardTotals($expenseOwnerId);
        $totalToday = (float) ($totals['today'] ?? 0);
        $totalThisMonth = (float) ($totals['month'] ?? 0);
        $totalOverall = (float) ($totals['overall'] ?? 0);
    }

    if (method_exists($expenseModel, 'getRecent')) {
        $recentExpenses = $expenseModel->getRecent($expenseOwnerId, 5) ?? [];
    }
} catch (Throwable $e) {
    error_log('Dashboard totals error: ' . $e->getMessage());
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new RuntimeException('Database connection ($conn) is not available.');
    }

    $chartSql = "
        SELECT DATE(expense_date) AS day,
               SUM(amount) AS total
        FROM expenses
        WHERE expense_date >= CURDATE() - INTERVAL 6 DAY
    ";

    if ($expenseOwnerId !== null) {
        $chartSql .= " AND user_id = :user_id";
    }

    $chartSql .= "
        GROUP BY DATE(expense_date)
        ORDER BY day ASC
    ";

    $stmt = $conn->prepare($chartSql);

    if ($expenseOwnerId !== null) {
        $stmt->bindValue(':user_id', $expenseOwnerId, PDO::PARAM_INT);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $chartLabels[] = date('M d', strtotime((string) $row['day']));
        $chartData[] = (float) $row['total'];
    }
} catch (Throwable $e) {
    error_log('Dashboard daily chart error: ' . $e->getMessage());
    $chartLabels = [];
    $chartData = [];
}

try {
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new RuntimeException('Database connection ($conn) is not available.');
    }

    $categorySql = "
        SELECT e.category AS category_name,
               SUM(e.amount) AS total
        FROM expenses e
        WHERE e.category IS NOT NULL
          AND e.category <> ''
    ";

    if ($expenseOwnerId !== null) {
        $categorySql .= " AND e.user_id = :user_id";
    }

    $categorySql .= "
        GROUP BY e.category
        ORDER BY total DESC
    ";

    $categoryStmt = $conn->prepare($categorySql);

    if ($expenseOwnerId !== null) {
        $categoryStmt->bindValue(':user_id', $expenseOwnerId, PDO::PARAM_INT);
    }

    $categoryStmt->execute();
    $categoryRows = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categoryRows as $row) {
        $categoryLabels[] = (string) $row['category_name'];
        $categoryData[] = (float) $row['total'];
    }
} catch (Throwable $e) {
    error_log('Dashboard category chart error: ' . $e->getMessage());
    $categoryLabels = [];
    $categoryData = [];
}