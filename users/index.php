<?php require_once '../config/database.php';
$database = new Database();
$conn = $database->connect();
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
        .action-icon { color: var(--text-secondary); margin-right: 12px; text-decoration: none; }
        .action-icon:hover { color: var(--accent); }
        .action-icon.delete:hover { color: #dc2626; }
        .users-table th { color: var(--text-secondary); font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--border-color); padding: 14px; }
        .users-table td { padding: 14px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); vertical-align: middle; }
        .role-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .role-badge.staff { background: #ecfdf5; color: #047857; }
    </style>
</head>
<body>

<div class="app-wrapper">

    <aside class="sidebar" id="mainSidebar">
        <a href="../views/dashboard.php" class="sidebar-brand" style="text-decoration: none;">
            <div class="brand-logo"><i class="fa-solid fa-wallet"></i></div>
            <span class="brand-text">Expense<b>MS</b></span>
        </a>
        <button class="collapse-toggle" id="collapseToggle" title="Collapse sidebar">
            <i class="fa-solid fa-angles-left"></i>
        </button>
        <nav class="sidebar-nav">
            <a href="../views/dashboard.php" class="nav-item" data-label="Dashboard">
                <i class="fa-solid fa-grid-2"></i> <span>Dashboard</span>
            </a>
            <a href="../expenses/index.php" class="nav-item" data-label="Expenses">
                <i class="fa-solid fa-receipt"></i> <span>Expenses</span>
            </a>
            <a href="../categories/index.php" class="nav-item" data-label="Categories">
                <i class="fa-solid fa-tags"></i> <span>Categories</span>
            </a>
            <a href="../reports/index.php" class="nav-item" data-label="Reports">
                <i class="fa-solid fa-chart-line"></i> <span>Reports</span>
            </a>
            <a href="index.php" class="nav-item active" data-label="Users">
                <i class="fa-solid fa-users"></i> <span>Users</span>
            </a>
            <a href="../settings/index.php" class="nav-item" data-label="Settings">
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
            <button class="icon-btn" id="sidebarToggle" title="Menu">
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

        <div class="panel">
            <div class="table-responsive">
                <table class="users-table w-100">
                    <thead>
                        <tr><th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Date Added</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin' ORDER BY id ASC");
                        $stmt->execute();
                        $users = $stmt->fetchAll();

                        if (count($users) === 0) {
                            echo "<tr><td colspan='7' class='text-center text-muted py-4'>No users found.</td></tr>";
                        } else {
                            $displayNumber = 1;
                            foreach ($users as $row) {
                                echo "<tr>
                                    <td>{$displayNumber}</td>
                                    <td>{$row['fullname']}</td>
                                    <td>{$row['username']}</td>
                                    <td>{$row['email']}</td>
                                    <td><span class='role-badge staff'>{$row['role']}</span></td>
                                    <td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>
                                    <td class='text-end'>
                                        <a href='edit_user.php?id={$row['id']}' class='action-icon'><i class='fa-solid fa-pen'></i></a>
                                        <a href='delete_user.php?id={$row['id']}' class='action-icon delete' onclick='return confirm(\"Delete this user?\")'><i class='fa-solid fa-trash'></i></a>
                                    </td>
                                </tr>";
                                $displayNumber++;
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

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.add('active');
        sidebarOverlay.classList.add('active');
    });

    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    });

    const collapseToggle = document.getElementById('collapseToggle');
    const mainSidebar = document.getElementById('mainSidebar');

    const savedCollapse = localStorage.getItem('sidebarCollapsed') === 'true';
    if (savedCollapse) mainSidebar.classList.add('collapsed');

    collapseToggle.addEventListener('click', () => {
        mainSidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', mainSidebar.classList.contains('collapsed'));
    });

    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
</script>
</body>
</html>