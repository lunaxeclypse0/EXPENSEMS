<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db_connect.php';
$currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .btn-add {
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-add:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .search-panel {
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .search-panel .form-control,
        .search-panel .form-select {
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .search-panel .form-control:focus,
        .search-panel .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124,92,255,0.15);
        }

        .btn-search {
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            border: none;
            padding: 8px 20px;
        }

        .btn-search:hover {
            background: #1d4ed8;
            color: #fff;
        }

        .btn-reset {
            background: var(--bg-main);
            color: var(--text-secondary);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 8px 20px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-reset:hover {
            color: var(--accent);
        }

        table.expense-table thead th a {
            color: var(--text-secondary);
            text-decoration: none;
        }

        table.expense-table thead th a:hover {
            color: var(--accent);
        }

        table.expense-table thead th a i {
            font-size: 11px;
            margin-left: 4px;
        }

        .badge-category {
            background: #eef2ff;
            color: #4338ca;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-payment {
            background: #ecfdf5;
            color: #047857;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .amount-text {
            font-weight: 600;
            color: #dc2626;
        }

        .action-icon {
            color: var(--text-secondary);
            margin-right: 12px;
            text-decoration: none;
        }

        .action-icon:hover {
            color: var(--accent);
        }

        .action-icon.delete:hover {
            color: #dc2626;
        }

        .panel,
        .search-panel {
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

            <a href="../users/index.php" class="nav-item <?php echo strpos($currentPath, '/users/') !== false ? 'active' : ''; ?>" data-label="Users">
                <i class="fa-solid fa-users"></i> <span>Users</span>
            </a>

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
                <h1 class="page-title">Expense Management</h1>
                <p class="page-sub">Track and manage all recorded expenses.</p>
            </div>

            <div class="topbar-right d-flex align-items-center gap-3">
                <a href="add_expense.php" class="btn-add">
                    <i class="fa-solid fa-plus me-2"></i>Add New Expense
                </a>
            </div>
        </div>

        <div class="panel search-panel">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Date</label>
                    <input type="date" name="search_date" class="form-control" value="<?php echo htmlspecialchars($_GET['search_date'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select name="search_category" class="form-select">
                        <option value="">All Categories</option>
                        <?php
                        $catStmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
                        $catStmt->execute();
                        while ($cat = $catStmt->fetch()) {
                            $selected = (($_GET['search_category'] ?? '') == $cat['name']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($cat['name']) . "' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Description</label>
                    <input type="text" name="search_description" class="form-control" placeholder="Search description..." value="<?php echo htmlspecialchars($_GET['search_description'] ?? ''); ?>">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn-search w-100">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                </div>

                <?php if (!empty($_GET['search_date']) || !empty($_GET['search_category']) || !empty($_GET['search_description'])): ?>
                <div class="col-12">
                    <a href="index.php" class="btn-reset mt-1">
                        <i class="fa-solid fa-rotate-left me-1"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="expense-table w-100">
                    <?php
                    $sort_by = $_GET['sort_by'] ?? 'expense_date';
                    $sort_order = $_GET['sort_order'] ?? 'DESC';
                    $next_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';

                    $allowed_sorts = ['expense_date', 'category', 'amount', 'payment_method'];
                    if (!in_array($sort_by, $allowed_sorts)) $sort_by = 'expense_date';
                    if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) $sort_order = 'DESC';

                    function sortLink($column, $label, $sort_by, $next_order, $sort_order) {
                        $params = $_GET;
                        $params['sort_by'] = $column;
                        $params['sort_order'] = ($sort_by == $column) ? $next_order : 'ASC';
                        $query = htmlspecialchars_decode(http_build_query($params));
                        $icon = '';
                        if ($sort_by == $column) {
                            $icon = ($sort_order == 'ASC') ? '<i class="fa-solid fa-arrow-up"></i>' : '<i class="fa-solid fa-arrow-down"></i>';
                        }
                        echo "<a href='index.php?$query'>$label $icon</a>";
                    }
                    ?>
                    <thead>
                        <tr>
                            <th><?php sortLink('expense_date', 'Date', $sort_by, $next_order, $sort_order); ?></th>
                            <th><?php sortLink('category', 'Category', $sort_by, $next_order, $sort_order); ?></th>
                            <th>Description</th>
                            <th><?php sortLink('amount', 'Amount', $sort_by, $next_order, $sort_order); ?></th>
                            <th><?php sortLink('payment_method', 'Payment Method', $sort_by, $next_order, $sort_order); ?></th>
                            <th>Remarks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $conditions = [];
                        $params = [];

                        if (!empty($_GET['search_date'])) {
                            $conditions[] = "expense_date = :search_date";
                            $params['search_date'] = $_GET['search_date'];
                        }

                        if (!empty($_GET['search_category'])) {
                            $conditions[] = "category = :search_category";
                            $params['search_category'] = $_GET['search_category'];
                        }

                        if (!empty($_GET['search_description'])) {
                            $conditions[] = "description LIKE :search_description";
                            $params['search_description'] = '%' . $_GET['search_description'] . '%';
                        }

                        $sql = "SELECT * FROM expenses";
                        if (count($conditions) > 0) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }
                        $sql .= " ORDER BY $sort_by $sort_order, id DESC";

                        $stmt = $conn->prepare($sql);
                        $stmt->execute($params);
                        $results = $stmt->fetchAll();

                        if (count($results) === 0) {
                            echo "<tr><td colspan='7' class='text-center text-muted py-4'>No matching expenses found.</td></tr>";
                        } else {
                            foreach ($results as $row) {
                                $id = (int)$row['id'];
                                $expenseDate = date('M d, Y', strtotime($row['expense_date']));
                                $category = htmlspecialchars($row['category']);
                                $description = htmlspecialchars($row['description']);
                                $amount = number_format((float)$row['amount'], 2);
                                $paymentMethod = htmlspecialchars($row['payment_method']);
                                $remarks = !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : '—';

                                echo "<tr>
                                    <td>{$expenseDate}</td>
                                    <td><span class='badge-category'>{$category}</span></td>
                                    <td>{$description}</td>
                                    <td class='amount-text'>₱{$amount}</td>
                                    <td><span class='badge-payment'>{$paymentMethod}</span></td>
                                    <td class='text-muted'>{$remarks}</td>
                                    <td class='text-end'>
                                        <a href='edit_expense.php?id={$id}' class='action-icon'><i class='fa-solid fa-pen'></i></a>
                                        <a href='delete_expense.php?id={$id}' class='action-icon delete' onclick='return confirm(\"Delete this expense?\")'><i class='fa-solid fa-trash'></i></a>
                                    </td>
                                </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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

    const savedCollapse = localStorage.getItem('sidebarCollapsed') === 'true';
    if (savedCollapse && mainSidebar) {
        mainSidebar.classList.add('collapsed');
    }

    if (collapseToggle) {
        collapseToggle.addEventListener('click', () => {
            mainSidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', mainSidebar.classList.contains('collapsed'));
        });
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</body>
</html>