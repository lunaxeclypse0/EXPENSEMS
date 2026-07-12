<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireAdmin();

$currentPath = $_SERVER['PHP_SELF'] ?? '';

$users = $conn->query('
    SELECT id, fullname, username, email, role, created_at
    FROM users
    ORDER BY fullname ASC, id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$success = (string) ($_GET['success'] ?? '');
$error = (string) ($_GET['error'] ?? '');

$successMessages = [
    'created' => 'User created successfully.',
    'updated' => 'User updated successfully.',
    'deleted' => 'User deleted successfully.',
];

$errorMessages = [
    'invalid_user' => 'The selected user is invalid.',
    'user_not_found' => 'The user could not be found.',
    'last_admin' => 'You cannot delete the last administrator.',
    'user_has_expenses' => 'This user cannot be deleted because they still have expenses.',
];

$successMessage = $successMessages[$success] ?? '';
$errorMessage = $errorMessages[$error] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .btn-add { background: #2563eb; color: #fff; border-radius: 8px; padding: 10px 20px; font-weight: 500; text-decoration: none; }
        .btn-add:hover { background: #1d4ed8; color: #fff; }
        .action-icon { color: var(--text-secondary); margin-right: 12px; text-decoration: none; background: none; border: none; padding: 0; font-size: 14px; cursor: pointer; }
        .action-icon:hover { color: var(--accent); }
        .action-icon.delete:hover { color: #dc2626; }
        .users-table th { color: var(--text-secondary); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); padding: 14px; }
        .users-table td { padding: 14px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); vertical-align: middle; }
        .role-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .role-badge.staff { background: #ecfdf5; color: #047857; }
        .role-badge.admin { background: #eff6ff; color: #1d4ed8; }
        .inline-delete-form { display: inline; }
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
            <a href="index.php" class="nav-item <?php echo strpos($currentPath, '/users/') !== false ? 'active' : ''; ?>" data-label="Users">
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
                <h1 class="page-title">User Management</h1>
                <p class="page-sub">Manage system users and their access roles.</p>
            </div>
            <div class="topbar-right d-flex align-items-center gap-3">
                <a href="add_user.php" class="btn-add"><i class="fa-solid fa-plus me-2"></i>Add User</a>
            </div>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="settings-alert success">
                <i class="fa-solid fa-circle-check"></i> <?php echo e($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="settings-alert error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="table-responsive">
                <table class="users-table w-100">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo e($user['fullname']); ?></td>
                                    <td><?php echo e($user['username']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo $user['role'] === 'admin' ? 'admin' : 'staff'; ?>">
                                            <?php echo e(ucfirst($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e(date('M d, Y', strtotime((string) $user['created_at']))); ?></td>
                                    <td class="text-end">
                                        <a class="action-icon" href="edit_user.php?id=<?php echo (int) $user['id']; ?>">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ((int) $user['id'] !== currentUserId()): ?>
                                            <form class="inline-delete-form" method="post" action="delete_user.php" onsubmit="return confirm('Delete this user?')">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
                                                <button class="action-icon delete" type="submit">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const collapseToggle = document.getElementById('collapseToggle');
    const mainSidebar = document.getElementById('mainSidebar');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        });
    }

    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }

    const savedCollapse = safeGetStorage('sidebarCollapsed', 'false') === 'true';
    if (savedCollapse && mainSidebar) {
        mainSidebar.classList.add('collapsed');
    }

    if (collapseToggle && mainSidebar) {
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