<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$expense = expenseInput([]);
$errors = [];

$catStmt = $conn->prepare('SELECT id, name FROM categories ORDER BY name ASC');
$catStmt->execute();
$categories = $catStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $expense = expenseInput($_POST);
    $errors = validateExpenseInput($conn, $expense);

    if (!$errors) {
        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare('
                INSERT INTO expenses
                (user_id, title, expense_date, category, description, amount, payment_method, remarks)
                VALUES
                (:user_id, :title, :expense_date, :category, :description, :amount, :payment_method, :remarks)
            ');

            $stmt->execute([
                'user_id' => currentUserId(),
                'title' => $expense['category'],
                'expense_date' => $expense['expense_date'],
                'category' => $expense['category'],
                'description' => $expense['description'],
                'amount' => $expense['amount'],
                'payment_method' => $expense['payment_method'],
                'remarks' => $expense['remarks'],
            ]);

            $notifMsg = 'New expense added: ' . $expense['description'] . ' - ₱' . number_format((float) $expense['amount'], 2);

            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, type, link)
                VALUES (:user_id, :msg, 'expense', '../expenses/index.php')
            ");
            $notifStmt->execute([
                'user_id' => currentUserId(),
                'msg' => $notifMsg,
            ]);

            $conn->commit();
            redirectTo('index.php?success=created');
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Add expense error: ' . $e->getMessage());
            $errors[] = 'Unable to save the expense. Please try again.';
        }
    }
}

$currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/expense-form.css">
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
            <a href="../views/dashboard.php"
            class="nav-item <?php echo strpos($currentPath, '/views/dashboard.php') !== false ? 'active' : ''; ?>"
            data-label="Dashboard">
                <i class="fa-solid fa-grid-2"></i> <span>Dashboard</span>
            </a>

            <a href="../expenses/index.php"
            class="nav-item <?php echo strpos($currentPath, '/expenses/') !== false ? 'active' : ''; ?>"
            data-label="Expenses">
                <i class="fa-solid fa-receipt"></i> <span>Expenses</span>
            </a>

            <a href="../categories/index.php"
            class="nav-item <?php echo strpos($currentPath, '/categories/') !== false ? 'active' : ''; ?>"
            data-label="Categories">
                <i class="fa-solid fa-tags"></i> <span>Categories</span>
            </a>

            <a href="../reports/index.php"
            class="nav-item <?php echo strpos($currentPath, '/reports/') !== false ? 'active' : ''; ?>"
            data-label="Reports">
                <i class="fa-solid fa-chart-line"></i> <span>Reports</span>
            </a>

            <?php if (isAdmin()): ?>
                <a href="../users/index.php"
                class="nav-item <?php echo strpos($currentPath, '/users/') !== false ? 'active' : ''; ?>"
                data-label="Users">
                    <i class="fa-solid fa-users"></i> <span>Users</span>
                </a>
            <?php endif; ?>

            <a href="../settings/index.php"
            class="nav-item <?php echo strpos($currentPath, '/settings/') !== false ? 'active' : ''; ?>"
            data-label="Settings">
                <i class="fa-solid fa-gear"></i> <span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item logout" data-label="Log out">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Log out</span>
            </a>
        </div>
    </aside>

    <main class="main-content expense-form-page">
        <div class="topbar">
            <button class="icon-btn" id="sidebarToggle" title="Menu" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h1 class="page-title">Add New Expense</h1>
                <p class="page-sub">Fill in the details below to record a new expense.</p>
            </div>
        </div>

        <div class="expense-form-container">
            <section class="panel expense-form-panel">
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="add_expense.php" novalidate>
                    <?php echo csrfField(); ?>

                    <div class="expense-form-grid">
                        <div class="expense-field">
                            <label for="expense_date">Expense Date</label>
                            <input id="expense_date" type="date" class="form-control" name="expense_date" value="<?php echo e($expense['expense_date']); ?>" required>
                        </div>

                        <div class="expense-field expense-field-full">
                            <div class="expense-field-head">
                                <label for="category">Category</label>
                                <?php if (isAdmin()): ?>
                                    <a href="../categories/index.php" class="manage-link">Manage Categories</a>
                                <?php endif; ?>
                            </div>

                            <select id="category" class="form-select" name="category" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo e($cat['name']); ?>" <?php echo $expense['category'] === $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="expense-field expense-field-full">
                            <label for="description">Description</label>
                            <textarea id="description" class="form-control" name="description" rows="4" placeholder="Brief details about this expense" required><?php echo e($expense['description']); ?></textarea>
                        </div>

                        <div class="expense-field">
                            <label for="amount">Amount (₱)</label>
                            <input id="amount" type="number" step="0.01" min="0.01" class="form-control" name="amount" placeholder="0.00" value="<?php echo e($expense['amount']); ?>" required>
                        </div>

                        <div class="expense-field">
                            <label for="payment_method">Payment Method</label>
                            <input id="payment_method" type="text" class="form-control" name="payment_method" maxlength="50" placeholder="e.g. Cash, GCash, Bank Transfer" value="<?php echo e($expense['payment_method']); ?>" required>
                        </div>

                        <div class="expense-field expense-field-full">
                            <label for="remarks">Remarks</label>
                            <textarea id="remarks" class="form-control" name="remarks" rows="3" placeholder="Optional notes"><?php echo e($expense['remarks']); ?></textarea>
                        </div>
                    </div>

                    <div class="expense-form-actions">
                        <a href="index.php" class="btn-cancel-expense">Cancel</a>
                        <button type="submit" class="btn-save-expense">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Expense</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

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