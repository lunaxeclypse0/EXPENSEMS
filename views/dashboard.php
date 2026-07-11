<?php require_once __DIR__ . '/../controllers/DashboardController.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<div class="app-wrapper">

    <aside class="sidebar" id="mainSidebar">
        <a href="dashboard.php" class="sidebar-brand" style="text-decoration: none;">
            <div class="brand-logo"><i class="fa-solid fa-wallet"></i></div>
            <span class="brand-text">Expense<b>MS</b></span>
        </a>

        <button class="collapse-toggle" id="collapseToggle" title="Collapse sidebar">
            <i class="fa-solid fa-angles-left"></i>
        </button>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active" data-label="Dashboard">
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
            <button class="icon-btn" id="sidebarToggle" title="Menu" type="button">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
                <p class="page-sub">It's the best time to manage your finances.</p>
            </div>

            <div class="topbar-right d-flex align-items-center gap-3">

                <form class="topbar-search" action="../expenses/index.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="search_description" class="search-input" placeholder="Search expenses, categories...">
                </form>

                <button class="icon-btn" id="themeToggle" title="Toggle dark/light mode" type="button">
                    <i class="fa-solid fa-moon" id="themeIcon"></i>
                </button>

                <div class="notif-wrapper">
                    <button class="icon-btn notif-btn" id="notifBtn" title="Notifications" type="button">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notif-dot" id="notifDot" style="display:none;"></span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-header">
                            <span>Notifications</span>
                            <a href="#" id="markAllRead">Mark all as read</a>
                        </div>

                        <div class="notif-dropdown-list" id="notifList">
                            <div class="notif-empty">No notifications yet.</div>
                        </div>
                    </div>
                </div>

                <div class="user-chip">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['fullname'], 0, 1)); ?></div>
                    <div class="user-info-text">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card teal">
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses Today</span>
                    <span class="stat-value">₱<?php echo number_format($totalToday, 2); ?></span>
                </div>
            </div>

            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses This Month</span>
                    <span class="stat-value">₱<?php echo number_format($totalThisMonth, 2); ?></span>
                </div>
            </div>

            <div class="stat-card dark">
                <div class="stat-icon"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses Overall</span>
                    <span class="stat-value">₱<?php echo number_format($totalOverall, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2>Recent Expenses</h2>
            </div>
            <table class="expense-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentExpenses)): ?>
                        <tr><td colspan="3" class="text-center text-muted">No expenses recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentExpenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['title']); ?></td>
                                <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</div>

<div class="notif-toast" id="notifToast">
    <div class="notif-toast-icon">
        <i class="fa-solid fa-bell"></i>
    </div>
    <div class="notif-toast-body">
        <div class="notif-toast-title">New notification</div>
        <div class="notif-toast-text" id="notifToastText">You have a new update.</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script>
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlEl = document.documentElement;

    function applyTheme(theme) {
        htmlEl.setAttribute('data-theme', theme);
        themeIcon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        localStorage.setItem('theme', theme);
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const currentTheme = htmlEl.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    });

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

    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const notifDot = document.getElementById('notifDot');
    const markAllRead = document.getElementById('markAllRead');
    const notifToast = document.getElementById('notifToast');
    const notifToastText = document.getElementById('notifToastText');

    let lastUnreadCount = 0;
    let isFirstLoad = true;

    function timeAgo(dateStr) {
        const seconds = Math.floor((new Date() - new Date(dateStr)) / 1000);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        return Math.floor(hours / 24) + 'd ago';
    }

    function triggerBellAlert(message) {
        notifBtn.classList.add('has-alert');
        notifBtn.classList.add('ringing');

        if (message) {
            notifToastText.textContent = message;
            notifToast.classList.add('show');

            setTimeout(() => {
                notifToast.classList.remove('show');
            }, 3200);
        }

        setTimeout(() => {
            notifBtn.classList.remove('ringing');
        }, 1400);
    }

    function loadNotifications() {
        fetch('../notifications/fetch.php')
            .then(res => res.json())
            .then(data => {
                const notifications = Array.isArray(data.notifications) ? data.notifications : [];
                const unreadCount = parseInt(data.unread || 0);

                notifDot.style.display = unreadCount > 0 ? 'block' : 'none';

                if (unreadCount > 0) {
                    notifBtn.classList.add('has-alert');
                } else {
                    notifBtn.classList.remove('has-alert');
                }

                if (notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                } else {
                    notifList.innerHTML = notifications.map(n => `
                        <a href="${n.link || '#'}" class="notif-item ${parseInt(n.is_read) === 1 ? '' : 'unread'}" data-id="${n.id}">
                            <div class="notif-icon ${n.type}">
                                <i class="fa-solid ${n.type === 'expense' ? 'fa-receipt' : n.type === 'user' ? 'fa-user' : 'fa-circle-info'}"></i>
                            </div>
                            <div class="notif-text">
                                <div class="notif-message">${n.message}</div>
                                <div class="notif-time">${timeAgo(n.created_at)}</div>
                            </div>
                        </a>
                    `).join('');
                }

                if (!isFirstLoad && unreadCount > lastUnreadCount && notifications.length > 0) {
                    const latestUnread = notifications.find(n => parseInt(n.is_read) === 0);
                    triggerBellAlert(latestUnread ? latestUnread.message : 'You have a new notification');
                }

                lastUnreadCount = unreadCount;
                isFirstLoad = false;
            })
            .catch(() => {
                notifList.innerHTML = '<div class="notif-empty">Failed to load notifications.</div>';
            });
    }

    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');

        if (notifDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });

    document.addEventListener('click', (e) => {
        if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });

    notifList.addEventListener('click', (e) => {
        const item = e.target.closest('.notif-item');
        if (item) {
            fetch('../notifications/mark_read.php?id=' + item.dataset.id)
                .then(() => loadNotifications());
        }
    });

    markAllRead.addEventListener('click', (e) => {
        e.preventDefault();
        fetch('../notifications/mark_read.php?all=1')
            .then(() => loadNotifications());
    });

    loadNotifications();
    setInterval(loadNotifications, 5000);
</script>
</body>
</html>