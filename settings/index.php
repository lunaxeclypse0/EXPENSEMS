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

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['remove_picture'])) {
        if (!empty($user['profile_picture']) && file_exists('../assets/uploads/profiles/' . $user['profile_picture'])) {
            unlink('../assets/uploads/profiles/' . $user['profile_picture']);
        }

        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = :id");
        $stmt->execute(['id' => $userId]);

        header("Location: index.php?removed=1");
        exit;
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fullname === '' || $username === '' || $email === '') {
        $error = 'Full name, username, and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id");
        $check->execute([
            'email' => $email,
            'username' => $username,
            'id' => $userId
        ]);

        if ($check->fetch()) {
            $error = 'Email or username already used by another account.';
        } else {
            if (!empty($_FILES['profile_image']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowed, true) && (int)$_FILES['profile_image']['size'] <= 10 * 1024 * 1024) {
                    $newFileName = 'user_' . $userId . '_' . time() . '.' . $ext;
                    $uploadPath = '../assets/uploads/profiles/' . $newFileName;

                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                        if (!empty($user['profile_picture']) && file_exists('../assets/uploads/profiles/' . $user['profile_picture'])) {
                            unlink('../assets/uploads/profiles/' . $user['profile_picture']);
                        }

                        $stmt = $conn->prepare("UPDATE users SET profile_picture = :pic WHERE id = :id");
                        $stmt->execute([
                            'pic' => $newFileName,
                            'id' => $userId
                        ]);
                    } else {
                        $error = 'Failed to upload image. Check folder permissions.';
                    }
                } else {
                    $error = 'Invalid file. Only JPG, PNG, GIF under 10MB allowed.';
                }
            }

            if ($error === '') {
                $stmt = $conn->prepare("UPDATE users SET fullname = :fullname, username = :username, email = :email WHERE id = :id");
                $stmt->execute([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email' => $email,
                    'id' => $userId
                ]);

                $_SESSION['fullname'] = $fullname;

                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($newPassword !== '') {
                    if (!password_verify($currentPassword, (string)$user['password'])) {
                        $error = 'Current password is incorrect.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'New passwords do not match.';
                    } else {
                        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

                        $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                        $stmt->execute([
                            'password' => $hashed,
                            'id' => $userId
                        ]);

                        $success = 'Profile and password updated successfully.';
                    }
                } else {
                    $success = 'Profile updated successfully.';
                }
            }
        }
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
}

if (isset($_GET['removed'])) {
    $success = 'Profile picture removed.';
}

$currentPath = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
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
            <a href="index.php" class="nav-item <?php echo strpos($currentPath, '/settings/') !== false ? 'active' : ''; ?>" data-label="Settings">
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
                <h1 class="page-title">Account Settings</h1>
                <p class="page-sub">Manage your profile and security preferences.</p>
            </div>
        </div>

        <div class="settings-wrapper">

            <?php if ($success): ?>
                <div class="settings-alert success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="settings-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="settings-panel">

                    <div class="settings-section">
                        <div class="settings-section-title">Profile Picture</div>
                        <div class="settings-section-desc">This is how others will recognize you across the system.</div>

                        <div class="avatar-block">
                            <div class="avatar-circle">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="../assets/uploads/profiles/<?php echo htmlspecialchars((string)$user['profile_picture']); ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo strtoupper(substr((string)$user['fullname'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="avatar-actions">
                                <div class="avatar-actions-buttons">
                                    <label for="profileImageInput" class="btn-upload"><i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Image</label>
                                    <input type="file" name="profile_image" id="profileImageInput" accept=".jpg,.jpeg,.png,.gif" style="display:none;" onchange="this.form.submit()">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <button type="submit" name="remove_picture" value="1" class="btn-remove">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="avatar-hint">We support PNGs, JPEGs and GIFs under 10MB</div>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="settings-field">
                                <label>Full Name</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars((string)$user['fullname']); ?>" required>
                            </div>
                            <div class="settings-field">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars((string)$user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="settings-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars((string)$user['email']); ?>" required>
                            <div class="field-note">Used to log in to your account.</div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="password-row">
                            <div class="password-row-text">
                                <div class="settings-section-title">Password</div>
                                <p>Update your password to keep your account secure.</p>
                            </div>
                            <button type="button" class="btn-outline-toggle" id="togglePassword">Change Password</button>
                        </div>

                        <div class="password-fields" id="passwordFields">
                            <div class="settings-form-row">
                                <div class="settings-field">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" placeholder="Enter current password">
                                </div>
                                <div class="settings-field">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" placeholder="Enter new password">
                                </div>
                            </div>
                            <div class="settings-field" style="max-width: 340px;">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="Re-enter new password">
                            </div>
                        </div>
                    </div>

                    <div class="settings-footer">
                        <a href="../views/dashboard.php" class="btn-cancel-settings">Cancel</a>
                        <button type="submit" class="btn-save-settings">Save Changes</button>
                    </div>

                </div>
            </form>

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
    if (savedCollapse) {
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

    const togglePassword = document.getElementById('togglePassword');
    const passwordFields = document.getElementById('passwordFields');

    if (togglePassword && passwordFields) {
        togglePassword.addEventListener('click', () => {
            passwordFields.classList.toggle('show');
            togglePassword.textContent = passwordFields.classList.contains('show') ? 'Hide' : 'Change Password';
        });
    }
</script>
</body>
</html>