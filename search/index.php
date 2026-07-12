<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$currentPath = $_SERVER['PHP_SELF'] ?? '';
$q = trim((string) ($_GET['q'] ?? ''));

$expenseResults = [];
$categoryResults = [];
$userResults = [];
$pageResults = [];

$systemPages = [
    [
        'title' => 'Dashboard',
        'link' => '../views/dashboard.php',
        'description' => 'View your dashboard summary and recent expenses.',
        'keywords' => ['dashboard', 'home', 'overview', 'main'],
    ],
    [
        'title' => 'Expenses',
        'link' => '../expenses/index.php',
        'description' => 'Manage and search all recorded expenses.',
        'keywords' => ['expense', 'expenses', 'amount', 'spending', 'cost', 'description'],
    ],
    [
        'title' => 'Categories',
        'link' => '../categories/index.php',
        'description' => 'Manage expense categories.',
        'keywords' => ['category', 'categories', 'tag', 'tags'],
    ],
    [
        'title' => 'Reports',
        'link' => '../reports/index.php',
        'description' => 'View expense reports and analytics.',
        'keywords' => ['report', 'reports', 'analytics', 'chart', 'summary'],
    ],
    [
        'title' => 'Users',
        'link' => '../users/index.php',
        'description' => 'Manage system users.',
        'keywords' => ['user', 'users', 'admin', 'administrator', 'account list'],
    ],
    [
        'title' => 'Settings',
        'link' => '../settings/index.php',
        'description' => 'Manage your account settings, profile, and password.',
        'keywords' => ['settings', 'setting', 'profile', 'account', 'password', 'security'],
    ],
];

if (!isAdmin()) {
    $systemPages = array_values(array_filter(
        $systemPages,
        static fn(array $page): bool => $page['title'] !== 'Users'
    ));
}

if ($q !== '') {
    $qLower = strtolower($q);

    foreach ($systemPages as $page) {
        if (strtolower($page['title']) === $qLower) {
            header('Location: ' . $page['link']);
            exit;
        }

        foreach ($page['keywords'] as $keyword) {
            if ($qLower === strtolower($keyword)) {
                header('Location: ' . $page['link']);
                exit;
            }
        }
    }

    foreach ($systemPages as $page) {
        $matched = false;

        if (
            stripos($page['title'], $q) !== false ||
            stripos($page['description'], $q) !== false
        ) {
            $matched = true;
        } else {
            foreach ($page['keywords'] as $keyword) {
                if (
                    stripos($keyword, $qLower) !== false ||
                    stripos($qLower, $keyword) !== false
                ) {
                    $matched = true;
                    break;
                }
            }
        }

        if ($matched) {
            $pageResults[] = $page;
        }
    }

    $searchTerm = '%' . $q . '%';

    if (isAdmin()) {
        $expenseSql = "
            SELECT id, title, amount, expense_date
            FROM expenses
            WHERE title LIKE :title_q
               OR description LIKE :description_q
            ORDER BY expense_date DESC
            LIMIT 10
        ";
        $expenseParams = [
            'title_q' => $searchTerm,
            'description_q' => $searchTerm,
        ];
    } else {
        $expenseSql = "
            SELECT id, title, amount, expense_date
            FROM expenses
            WHERE (title LIKE :title_q OR description LIKE :description_q)
              AND user_id = :user_id
            ORDER BY expense_date DESC
            LIMIT 10
        ";
        $expenseParams = [
            'title_q' => $searchTerm,
            'description_q' => $searchTerm,
            'user_id' => currentUserId(),
        ];
    }

    $stmt = $conn->prepare($expenseSql);
    $stmt->execute($expenseParams);
    $expenseResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('
        SELECT id, name
        FROM categories
        WHERE name LIKE :category_q
        ORDER BY name ASC
        LIMIT 10
    ');
    $stmt->execute(['category_q' => $searchTerm]);
    $categoryResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isAdmin()) {
        $stmt = $conn->prepare('
            SELECT id, fullname, username, email
            FROM users
            WHERE fullname LIKE :fullname_q
               OR username LIKE :username_q
               OR email LIKE :email_q
            ORDER BY fullname ASC
            LIMIT 10
        ');
        $stmt->execute([
            'fullname_q' => $searchTerm,
            'username_q' => $searchTerm,
            'email_q' => $searchTerm,
        ]);
        $userResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (count($pageResults) === 1 && empty($expenseResults) && empty($categoryResults) && empty($userResults)) {
        header('Location: ' . $pageResults[0]['link']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search | Expense Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <style>
        .search-page-box {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .search-form-global {
            position: relative;
            max-width: 700px;
        }

        .search-form-global .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-form-global input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--input-bg, #fff);
            color: var(--text-primary);
            padding: 0 16px 0 44px;
            outline: none;
        }

        .search-section {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .search-section h3 {
            font-size: 18px;
            margin-bottom: 14px;
        }

        .search-result-item {
            display: block;
            text-decoration: none;
            color: var(--text-primary);
            padding: 14px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .search-result-meta {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .empty-search {
            color: var(--text-secondary);
            font-size: 14px;
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
                <h1 class="page-title">Global Search</h1>
                <p class="page-sub">Search pages, expenses, categories, and users in one place.</p>
            </div>
        </div>

        <div class="search-page-box">
            <form class="search-form-global" method="GET" action="index.php">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" placeholder="Search the whole system..." value="<?php echo e($q); ?>">
            </form>
        </div>

        <?php if ($q === ''): ?>
            <div class="search-section">
                <div class="empty-search">Type a keyword to search across the system.</div>
            </div>
        <?php else: ?>

            <div class="search-section">
                <h3>Pages</h3>
                <?php if (empty($pageResults)): ?>
                    <div class="empty-search">No matching pages found.</div>
                <?php else: ?>
                    <?php foreach ($pageResults as $page): ?>
                        <a href="<?php echo e($page['link']); ?>" class="search-result-item">
                            <div class="search-result-title"><?php echo e($page['title']); ?></div>
                            <div class="search-result-meta"><?php echo e($page['description']); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="search-section">
                <h3>Expenses</h3>
                <?php if (empty($expenseResults)): ?>
                    <div class="empty-search">No matching expenses found.</div>
                <?php else: ?>
                    <?php foreach ($expenseResults as $row): ?>
                        <a href="../expenses/index.php?search_description=<?php echo urlencode($q); ?>" class="search-result-item">
                            <div class="search-result-title"><?php echo e($row['title'] ?? 'Untitled Expense'); ?></div>
                            <div class="search-result-meta">
                                ₱<?php echo number_format((float) ($row['amount'] ?? 0), 2); ?> •
                                <?php echo e(date('M d, Y', strtotime((string) $row['expense_date']))); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="search-section">
                <h3>Categories</h3>
                <?php if (empty($categoryResults)): ?>
                    <div class="empty-search">No matching categories found.</div>
                <?php else: ?>
                    <?php foreach ($categoryResults as $row): ?>
                        <a href="../categories/index.php" class="search-result-item">
                            <div class="search-result-title"><?php echo e($row['name']); ?></div>
                            <div class="search-result-meta">Category ID: <?php echo (int) $row['id']; ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isAdmin()): ?>
                <div class="search-section">
                    <h3>Users</h3>
                    <?php if (empty($userResults)): ?>
                        <div class="empty-search">No matching users found.</div>
                    <?php else: ?>
                        <?php foreach ($userResults as $row): ?>
                            <a href="../users/index.php" class="search-result-item">
                                <div class="search-result-title"><?php echo e($row['fullname']); ?></div>
                                <div class="search-result-meta">
                                    @<?php echo e($row['username']); ?> •
                                    <?php echo e($row['email']); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
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