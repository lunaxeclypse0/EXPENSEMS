<?php
declare(strict_types=1);

require_once __DIR__ . '/../controllers/DashboardController.php';

$currentPath = $_SERVER['PHP_SELF'] ?? '';

function dashboardProfileImageUrl(?string $filename): ?string
{
    if (
        !$filename ||
        basename($filename) !== $filename ||
        !preg_match('/^user_\d+_[a-f0-9]{32}\.(jpg|jpeg|png|gif)$/i', $filename)
    ) {
        return null;
    }

    $absolutePath = __DIR__ . '/../assets/uploads/profiles/' . $filename;
    if (!is_file($absolutePath)) {
        return null;
    }

    return '../assets/uploads/profiles/' . rawurlencode($filename) . '?v=' . filemtime($absolutePath);
}

$currentUserFullname = (string) ($_SESSION['fullname'] ?? 'User');
$currentUserRole = (string) ($_SESSION['role'] ?? 'User');
$currentUserInitial = strtoupper(substr($currentUserFullname, 0, 1));
$currentUserProfilePicture = $_SESSION['profile_picture'] ?? null;
$currentUserAvatarUrl = dashboardProfileImageUrl(
    is_string($currentUserProfilePicture) ? $currentUserProfilePicture : null
);
?>
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
    <style>
        .user-avatar {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
    </style>
</head>
<body>

<div class="app-wrapper">

    <aside class="sidebar" id="mainSidebar">
        <a href="dashboard.php" class="sidebar-brand" style="text-decoration: none;">
            <div class="brand-logo"><i class="fa-solid fa-wallet"></i></div>
            <span class="brand-text">Expense<b>MS</b></span>
        </a>

        <button class="collapse-toggle" id="collapseToggle" title="Collapse sidebar" type="button">
            <i class="fa-solid fa-angles-left"></i>
        </button>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?php echo strpos($currentPath, '/views/dashboard.php') !== false ? 'active' : ''; ?>" data-label="Dashboard">
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
                <h1 class="page-title">
                    Welcome back, <?php echo htmlspecialchars($currentUserFullname); ?>!
                </h1>
                <p class="page-sub">It's the best time to manage your finances.</p>
            </div>

            <div class="topbar-right d-flex align-items-center gap-3">

                <form class="topbar-search" action="../search/index.php" method="GET">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        name="q"
                        class="search-input"
                        placeholder="Search pages, expenses, categories, users..."
                        autocomplete="off"
                    >
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
                    <div class="user-avatar" id="dashboardAvatar">
                        <?php if ($currentUserAvatarUrl !== null): ?>
                            <img
                                src="<?php echo htmlspecialchars($currentUserAvatarUrl); ?>"
                                alt="<?php echo htmlspecialchars($currentUserFullname); ?>"
                                onerror="this.remove(); this.parentNode.textContent='<?php echo htmlspecialchars($currentUserInitial, ENT_QUOTES); ?>';"
                            >
                        <?php else: ?>
                            <?php echo htmlspecialchars($currentUserInitial); ?>
                        <?php endif; ?>
                    </div>

                    <div class="user-info-text">
                        <div class="user-name"><?php echo htmlspecialchars($currentUserFullname); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($currentUserRole); ?></div>
                    </div>
                </div>

            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card teal">
                <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses Today</span>
                    <span class="stat-value">₱<?php echo number_format((float) $totalToday, 2); ?></span>
                </div>
            </div>

            <div class="stat-card blue">
                <div class="stat-icon"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses This Month</span>
                    <span class="stat-value">₱<?php echo number_format((float) $totalThisMonth, 2); ?></span>
                </div>
            </div>

            <div class="stat-card dark">
                <div class="stat-icon"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses Overall</span>
                    <span class="stat-value">₱<?php echo number_format((float) $totalOverall, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="panel">
                <div class="panel-header">
                    <h2>Last 7 Days Spending</h2>
                </div>
                <?php if (empty($chartLabels)): ?>
                    <p class="text-center text-muted py-4">No recent expense data.</p>
                <?php else: ?>
                    <canvas id="dailyChart" height="240"></canvas>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2>Expense Breakdown by Category</h2>
                </div>
                <?php if (empty($categoryLabels)): ?>
                    <p class="text-center text-muted py-4">No category data yet.</p>
                <?php else: ?>
                    <canvas id="categoryChart" height="240"></canvas>
                <?php endif; ?>
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
                        <tr>
                            <td colspan="3" class="text-center text-muted">No expenses recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentExpenses as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) $expense['title']); ?></td>
                                <td>₱<?php echo number_format((float) $expense['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime((string) $expense['expense_date'])); ?></td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>window.CSRF_TOKEN = <?php echo json_encode(csrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="../assets/js/dashboard.js"></script>
<script>
    const dailyLabels = <?php echo json_encode($chartLabels); ?>;
    const dailyData = <?php echo json_encode($chartData); ?>;
    const categoryLabels = <?php echo json_encode($categoryLabels); ?>;
    const categoryData = <?php echo json_encode($categoryData); ?>;

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

    const dailyCanvas = document.getElementById('dailyChart');
    if (dailyCanvas && dailyLabels.length > 0) {
        new Chart(dailyCanvas, {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Expenses',
                    data: dailyData,
                    borderColor: '#7c5cff',
                    backgroundColor: 'rgba(124, 92, 255, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')
                        }
                    },
                    y: {
                        ticks: {
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas && categoryLabels.length > 0) {
        new Chart(categoryCanvas, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: ['#7c5cff', '#2563eb', '#14b8a6', '#f59e0b', '#ef4444', '#9b87ff', '#0ea5e9', '#f97316']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary')
                        }
                    }
                }
            }
        });
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlEl = document.documentElement;

    function applyTheme(theme) {
        htmlEl.setAttribute('data-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        safeSetStorage('theme', theme);
    }

    const savedTheme = safeGetStorage('theme', 'light');
    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlEl.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

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

    const collapseToggle = document.getElementById('collapseToggle');
    const mainSidebar = document.getElementById('mainSidebar');

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

    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const notifDot = document.getElementById('notifDot');
    const markAllRead = document.getElementById('markAllRead');
    const notifToast = document.getElementById('notifToast');
    const notifToastText = document.getElementById('notifToastText');

    let lastUnreadCount = 0;
    let isFirstLoad = true;
    const csrfToken = window.CSRF_TOKEN || '';

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    }

    function safeNotificationLink(value) {
        try {
            const url = new URL(String(value || '#'), window.location.href);
            return url.origin === window.location.origin ? url.href : '#';
        } catch (_) {
            return '#';
        }
    }

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
        if (!notifBtn || !notifToast || !notifToastText) return;

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
        if (!notifList || !notifDot || !notifBtn) return;

        fetch('../notifications/fetch.php')
            .then(res => res.json())
            .then(data => {
                const notifications = Array.isArray(data.notifications) ? data.notifications : [];
                const unreadCount = parseInt(data.unread || 0, 10);

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
                        <a href="${safeNotificationLink(n.link)}" class="notif-item ${parseInt(n.is_read, 10) === 1 ? '' : 'unread'}" data-id="${parseInt(n.id, 10) || 0}">
                            <div class="notif-icon ${escapeHtml(['expense', 'user'].includes(n.type) ? n.type : 'info')}">
                                <i class="fa-solid ${n.type === 'expense' ? 'fa-receipt' : n.type === 'user' ? 'fa-user' : 'fa-circle-info'}"></i>
                            </div>
                            <div class="notif-text">
                                <div class="notif-message">${escapeHtml(n.message)}</div>
                                <div class="notif-time">${escapeHtml(timeAgo(n.created_at))}</div>
                            </div>
                        </a>
                    `).join('');
                }

                if (!isFirstLoad && unreadCount > lastUnreadCount && notifications.length > 0) {
                    const latestUnread = notifications.find(n => parseInt(n.is_read, 10) === 0);
                    triggerBellAlert(latestUnread ? latestUnread.message : 'You have a new notification');
                }

                lastUnreadCount = unreadCount;
                isFirstLoad = false;
            })
            .catch(() => {
                notifList.innerHTML = '<div class="notif-empty">Failed to load notifications.</div>';
            });
    }

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');

            if (notifDropdown.classList.contains('show')) {
                loadNotifications();
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (notifDropdown && notifBtn && !notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
    });

    if (notifList) {
        notifList.addEventListener('click', (e) => {
            const item = e.target.closest('.notif-item');
            if (item) {
                fetch('../notifications/mark_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body: 'id=' + encodeURIComponent(item.dataset.id)
                }).then(() => loadNotifications());
            }
        });
    }

    if (markAllRead) {
        markAllRead.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('../notifications/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken
                },
                body: 'all=1'
            }).then(() => loadNotifications());
        });
    }

    loadNotifications();
    setInterval(loadNotifications, 5000);
</script>
</body>
</html>