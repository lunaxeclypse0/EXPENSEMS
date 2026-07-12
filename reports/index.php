<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$currentPath = $_SERVER['PHP_SELF'] ?? '';

$report_type = (string) ($_GET['report_type'] ?? 'daily');
$allowedReportTypes = ['daily', 'monthly', 'range'];

if (!in_array($report_type, $allowedReportTypes, true)) {
    $report_type = 'daily';
}

$conditions = [];
$params = [];
$results = [];
$grand_total = 0.0;
$message = '';

if (!isAdmin()) {
    $conditions[] = 'user_id = :user_id';
    $params['user_id'] = currentUserId();
}

if ($report_type === 'daily') {
    $date = (string) ($_GET['report_date'] ?? date('Y-m-d'));

    if (!validDate($date)) {
        $message = 'Please select a valid report date.';
    } else {
        $conditions[] = 'expense_date = :date';
        $params['date'] = $date;
    }
} elseif ($report_type === 'monthly') {
    $month = (string) ($_GET['report_month'] ?? date('Y-m'));

    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
        $message = 'Please select a valid report month.';
    } else {
        $conditions[] = 'expense_date >= :month_start AND expense_date < :month_end';
        $params['month_start'] = $month . '-01';
        $params['month_end'] = (new DateTimeImmutable($month . '-01'))
            ->modify('+1 month')
            ->format('Y-m-d');
    }
} else {
    $from = (string) ($_GET['date_from'] ?? '');
    $to = (string) ($_GET['date_to'] ?? '');

    if (!validDate($from) || !validDate($to)) {
        $message = 'Please select both From and To dates.';
    } elseif ($from > $to) {
        $message = 'From Date cannot be later than To Date.';
    } else {
        $conditions[] = 'expense_date BETWEEN :date_from AND :date_to';
        $params['date_from'] = $from;
        $params['date_to'] = $to;
    }
}

if ($message === '') {
    $sql = 'SELECT id, expense_date, category, description, amount FROM expenses';

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY expense_date ASC, id ASC';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $grand_total += (float) ($row['amount'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Reports | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .nav-pills .nav-link {
            border-radius: 8px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .nav-pills .nav-link.active {
            background: var(--accent);
            color: #fff;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124, 92, 255, 0.15);
        }

        .btn-generate {
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            border: none;
            padding: 8px 24px;
        }

        .btn-generate:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .report-table thead th {
            background: var(--bg-main);
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            padding: 14px;
        }

        .report-table tbody td,
        .report-table tfoot td {
            padding: 14px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .badge-category {
            background: #eef2ff;
            color: #4338ca;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }

        .amount-text {
            font-weight: 600;
            color: #dc2626;
        }

        .grand-total-row {
            background: var(--bg-main);
            font-weight: 700;
            font-size: 16px;
            color: var(--text-primary);
        }

        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: var(--accent);
        }

        .panel {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>

<div class="app-wrapper">

    <aside class="sidebar" id="mainSidebar">
        <a href="../views/dashboard.php" class="sidebar-brand" style="text-decoration: none;">
            <div class="brand-logo"><i class="fa-solid fa-wallet"></i></div>
            <span class="brand-text">Expense<b>MS</b></span>
        </a>

        <button class="collapse-toggle" id="collapseToggle" title="Collapse sidebar" type="button">
            <i class="fa-solid fa-angles-left"></i>
        </button>

        <nav class="sidebar-nav">
            <a href="../views/dashboard.php" class="nav-item <?php echo strpos($currentPath, '/views/dashboard.php') !== false ? 'active' : ''; ?>" data-label="Dashboard">
                <i class="fa-solid fa-grid-2"></i> <span>Dashboard</span>
            </a>

            <a href="../expenses/index.php" class="nav-item <?php echo strpos($currentPath, '/expenses/') !== false ? 'active' : ''; ?>" data-label="Expenses">
                <i class="fa-solid fa-receipt"></i> <span>Expenses</span>
            </a>

            <a href="../categories/index.php" class="nav-item <?php echo strpos($currentPath, '/categories/') !== false ? 'active' : ''; ?>" data-label="Categories">
                <i class="fa-solid fa-tags"></i> <span>Categories</span>
            </a>

            <a href="../reports/index.php" class="nav-item <?php echo strpos($currentPath, '/reports/') !== false ? 'active' : ''; ?>" data-label="Reports">
                <i class="fa-solid fa-chart-line"></i> <span>Reports</span>
            </a>

            <?php if (isAdmin()): ?>
                <a href="../users/index.php" class="nav-item <?php echo strpos($currentPath, '/users/') !== false ? 'active' : ''; ?>" data-label="Users">
                    <i class="fa-solid fa-users"></i> <span>Users</span>
                </a>
            <?php endif; ?>

            <a href="../settings/index.php" class="nav-item <?php echo strpos($currentPath, '/settings/') !== false ? 'active' : ''; ?>" data-label="Settings">
                <i class="fa-solid fa-gear"></i> <span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" id="logoutBtn" class="nav-item logout" data-label="Log out">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Log out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">

        <div class="topbar">
            <button class="icon-btn" id="sidebarToggle" title="Menu" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h1 class="page-title">Expense Reports</h1>
                <p class="page-sub">Generate daily, monthly, or custom date range reports.</p>
            </div>
        </div>

        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $report_type === 'daily' ? 'active' : ''; ?>" href="index.php?report_type=daily">Daily Report</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $report_type === 'monthly' ? 'active' : ''; ?>" href="index.php?report_type=monthly">Monthly Report</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $report_type === 'range' ? 'active' : ''; ?>" href="index.php?report_type=range">Date Range Report</a>
            </li>
        </ul>

        <div class="panel mb-4">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <input type="hidden" name="report_type" value="<?php echo e($report_type); ?>">

                <?php if ($report_type === 'daily'): ?>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Select Date</label>
                        <input type="date" name="report_date" class="form-control" value="<?php echo e($_GET['report_date'] ?? date('Y-m-d')); ?>">
                    </div>
                <?php elseif ($report_type === 'monthly'): ?>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Select Month</label>
                        <input type="month" name="report_month" class="form-control" value="<?php echo e($_GET['report_month'] ?? date('Y-m')); ?>">
                    </div>
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo e($_GET['date_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo e($_GET['date_to'] ?? ''); ?>">
                    </div>
                <?php endif; ?>

                <div class="col-md-3">
                    <button type="submit" class="btn-generate">
                        <i class="fa-solid fa-magnifying-glass-chart me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="report-table w-100">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($message !== ''): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4"><?php echo e($message); ?></td>
                            </tr>
                        <?php elseif (count($results) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No expenses found for this period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <td><?php echo e(date('M d, Y', strtotime((string) $row['expense_date']))); ?></td>
                                    <td><span class="badge-category"><?php echo e($row['category'] ?? 'Uncategorized'); ?></span></td>
                                    <td><?php echo e($row['description'] ?? ''); ?></td>
                                    <td class="text-end amount-text">₱<?php echo number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <?php if (count($results) > 0): ?>
                        <tfoot>
                            <tr class="grand-total-row">
                                <td colspan="3" class="text-end">Grand Total Expenses:</td>
                                <td class="text-end">₱<?php echo number_format($grand_total, 2); ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <a href="../expenses/index.php" class="back-link d-inline-block mt-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Expenses
            </a>
        </div>

    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const collapseToggle = document.getElementById('collapseToggle');
    const mainSidebar = document.getElementById('mainSidebar');

    function safeGetStorage(key, fallback = null) {
        try {
            const value = window.localStorage.getItem(key);
            return value !== null ? value : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function safeSetStorage(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch (e) {}
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    const savedCollapse = safeGetStorage('sidebarCollapsed', 'false') === 'true';
    if (savedCollapse && mainSidebar) {
        mainSidebar.classList.add('collapsed');
    }

    if (collapseToggle) {
        collapseToggle.addEventListener('click', () => {
            mainSidebar.classList.toggle('collapsed');
            safeSetStorage('sidebarCollapsed', String(mainSidebar.classList.contains('collapsed')));
        });
    }

    const savedTheme = safeGetStorage('theme', 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</body>
</html>