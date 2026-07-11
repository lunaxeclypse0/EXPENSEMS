<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (:name)");
    $stmt->execute(['name' => $name]);
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
            <a href="index.php" class="nav-item active" data-label="Categories">
                <i class="fa-solid fa-tags"></i> <span>Categories</span>
            </a>
            <a href="../reports/index.php" class="nav-item" data-label="Reports">
                <i class="fa-solid fa-chart-line"></i> <span>Reports</span>
            </a>
            <a href="../users/index.php" class="nav-item" data-label="Users">
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
                <h1 class="page-title">Add Category</h1>
                <p class="page-sub">Create a new expense category.</p>
            </div>
        </div>

        <div class="panel" style="max-width: 500px;">
            <form method="POST">
                <label class="fw-medium mb-2 d-block">Category Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Software Subscription" required>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="index.php" class="btn-cancel d-flex align-items-center">Cancel</a>
                    <button type="submit" class="btn-add" style="border:none;">Save</button>
                </div>
            </form>
        </div>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<style>
    .btn-add { background: #2563eb; color: #fff; border-radius: 8px; padding: 10px 24px; font-weight: 500; }
    .btn-add:hover { background: #1d4ed8; color: #fff; }
    .btn-cancel { color: var(--text-secondary); text-decoration: none; padding: 10px 20px; }
    .btn-cancel:hover { color: var(--accent); }
    .form-control { border-radius: 8px; padding: 10px 14px; background: var(--bg-card); color: var(--text-primary); border: 1px solid var(--border-color); }
</style>

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