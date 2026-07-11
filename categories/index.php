<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Expense Management System</title>

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

        .panel {
            position: relative;
            z-index: 1;
        }

        .categories-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .categories-table col.id-col {
            width: 84px;
        }

        .categories-table col.name-col {
            width: auto;
        }

        .categories-table col.actions-col {
            width: 126px;
        }

        .categories-table th {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
            padding: 14px 16px;
            white-space: nowrap;
            letter-spacing: 0.04em;
        }

        .categories-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .categories-table tbody tr:hover {
            background: rgba(37, 99, 235, 0.04);
        }

        .categories-table th.id-head,
        .categories-table td.id-cell {
            text-align: center;
        }

        .categories-table th.name-head,
        .categories-table td.name-cell {
            text-align: left;
        }

        .categories-table th.actions-head,
        .categories-table td.actions-cell {
            text-align: right;
            white-space: nowrap;
        }

        .id-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.08);
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
        }

        .name-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .name-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            flex-shrink: 0;
            opacity: 0.9;
        }

        .name-text {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            line-height: 1.35;
            word-break: break-word;
        }

        .sort-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: none;
            background: rgba(37, 99, 235, 0.08);
            color: var(--accent);
            vertical-align: middle;
        }

        .action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .action-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .action-icon:hover {
            color: var(--accent);
            background: rgba(37, 99, 235, 0.08);
        }

        .action-icon.delete:hover {
            color: #dc2626;
            background: rgba(220, 38, 38, 0.08);
        }

        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .categories-table col.id-col {
                width: 68px;
            }

            .categories-table col.actions-col {
                width: 104px;
            }

            .categories-table th,
            .categories-table td {
                padding: 12px 10px;
            }

            .id-badge {
                min-width: 34px;
                height: 28px;
                font-size: 12px;
            }

            .name-wrap {
                gap: 10px;
            }

            .name-text {
                font-size: 13px;
            }

            .action-icon {
                width: 32px;
                height: 32px;
            }

            .sort-badge {
                display: none;
            }
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
                <h1 class="page-title">Expense Categories</h1>
                <p class="page-sub">Manage the list of available expense categories.</p>
            </div>

            <div class="topbar-right d-flex align-items-center gap-3">
                <a href="add_category.php" class="btn-add">
                    <i class="fa-solid fa-plus me-2"></i>Add Category
                </a>
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="categories-table">
                    <colgroup>
                        <col class="id-col">
                        <col class="name-col">
                        <col class="actions-col">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="id-head">ID</th>
                            <th class="name-head">
                                Category Name
                                <span class="sort-badge">
                                    <i class="fa-solid fa-arrow-down-a-z"></i> A-Z
                                </span>
                            </th>
                            <th class="actions-head">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
                        $stmt->execute();
                        $categories = $stmt->fetchAll();

                        if (count($categories) === 0) {
                            echo "<tr><td colspan='3' class='text-center text-muted py-4'>No categories found.</td></tr>";
                        } else {
                            foreach ($categories as $row) {
                                $id = (int)$row['id'];
                                $name = htmlspecialchars(trim($row['name']));

                                echo "
                                <tr>
                                    <td class='id-cell'>
                                        <span class='id-badge'>{$id}</span>
                                    </td>
                                    <td class='name-cell'>
                                        <div class='name-wrap'>
                                            <span class='name-dot'></span>
                                            <span class='name-text'>{$name}</span>
                                        </div>
                                    </td>
                                    <td class='actions-cell'>
                                        <div class='action-group'>
                                            <a href='edit_category.php?id={$id}' class='action-icon' title='Edit'>
                                                <i class='fa-solid fa-pen'></i>
                                            </a>
                                            <a href='delete_category.php?id={$id}' class='action-icon delete' title='Delete' onclick='return confirm(\"Delete this category?\")'>
                                                <i class='fa-solid fa-trash'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <a href="../views/dashboard.php" class="back-link d-inline-block mt-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
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