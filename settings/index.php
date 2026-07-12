<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$userId = currentUserId();

$loadUser = $conn->prepare('
    SELECT id, fullname, username, email, password, profile_picture, role
    FROM users
    WHERE id = :id
    LIMIT 1
');
$loadUser->execute(['id' => $userId]);
$user = $loadUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    destroyCurrentSession();
    redirectTo(BASE_URL . 'views/login.php');
}

$success = '';
$error = '';
$profileDirectory = __DIR__ . '/../assets/uploads/profiles';

function profileFilePath(string $directory, ?string $filename): ?string
{
    if (
        !$filename ||
        basename($filename) !== $filename ||
        !preg_match('/^user_\d+_[a-f0-9]{32}\.(jpg|jpeg|png|gif)$/i', $filename)
    ) {
        return null;
    }

    return $directory . DIRECTORY_SEPARATOR . $filename;
}

function profileImageUrl(?string $filename): ?string
{
    if (
        !$filename ||
        basename($filename) !== $filename ||
        !preg_match('/^user_\d+_[a-f0-9]{32}\.(jpg|jpeg|png|gif)$/i', $filename)
    ) {
        return null;
    }

    $basePath = __DIR__ . '/../assets/uploads/profiles/' . $filename;
    if (!is_file($basePath)) {
        return null;
    }

    $url = '../assets/uploads/profiles/' . rawurlencode($filename) . '?v=' . filemtime($basePath);
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    if (isset($_POST['remove_picture'])) {
        $path = profileFilePath($profileDirectory, $user['profile_picture'] ?? null);

        $conn->prepare('UPDATE users SET profile_picture = NULL WHERE id = :id')
            ->execute(['id' => $userId]);

        if ($path && is_file($path)) {
            @unlink($path);
        }

        $_SESSION['profile_picture'] = null;

        redirectTo('index.php?picture_removed=1');
    }

    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $newFileName = null;

    if (
        $fullname === '' ||
        mb_strlen($fullname) > 100 ||
        !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = 'Please provide a valid full name, username, and email address.';
    } elseif ($newPassword !== '' && (strlen($newPassword) < 12 || !hash_equals($newPassword, $confirmPassword))) {
        $error = 'New passwords must match and be at least 12 characters.';
    } elseif ($newPassword !== '' && !password_verify($currentPassword, (string) ($user['password'] ?? ''))) {
        $error = 'Your current password is incorrect.';
    } else {
        $duplicate = $conn->prepare('
            SELECT id
            FROM users
            WHERE (username = :username OR email = :email) AND id != :id
            LIMIT 1
        ');
        $duplicate->execute([
            'username' => $username,
            'email' => $email,
            'id' => $userId,
        ]);

        if ($duplicate->fetch(PDO::FETCH_ASSOC)) {
            $error = 'That username or email address belongs to another account.';
        }
    }

    if (
        $error === '' &&
        isset($_FILES['profile_image']) &&
        is_array($_FILES['profile_image']) &&
        ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        $upload = $_FILES['profile_image'];
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_OK);
        $uploadSize = (int) ($upload['size'] ?? 0);

        $mime = is_uploaded_file($tmpName)
            ? (new finfo(FILEINFO_MIME_TYPE))->file($tmpName)
            : false;

        if (
            $uploadError !== UPLOAD_ERR_OK ||
            $uploadSize <= 0 ||
            $uploadSize > 5 * 1024 * 1024 ||
            !$mime ||
            !isset($allowedTypes[$mime]) ||
            !@getimagesize($tmpName)
        ) {
            $error = 'Upload a valid JPEG, PNG, or GIF image smaller than 5 MB.';
        } else {
            if (!is_dir($profileDirectory) && !mkdir($profileDirectory, 0755, true) && !is_dir($profileDirectory)) {
                $error = 'Profile image storage is not available.';
            } elseif (!is_writable($profileDirectory)) {
                $error = 'Profile image folder is not writable.';
            } else {
                $newFileName = 'user_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                $destination = $profileDirectory . DIRECTORY_SEPARATOR . $newFileName;

                if (!move_uploaded_file($tmpName, $destination)) {
                    $error = 'Unable to save the profile image.';
                }
            }
        }
    }

    if ($error === '') {
        $oldPicturePath = profileFilePath($profileDirectory, $user['profile_picture'] ?? null);

        $conn->beginTransaction();

        try {
            $sql = 'UPDATE users SET fullname = :fullname, username = :username, email = :email';
            $params = [
                'fullname' => $fullname,
                'username' => $username,
                'email' => $email,
                'id' => $userId,
            ];

            if ($newFileName !== null) {
                $sql .= ', profile_picture = :profile_picture';
                $params['profile_picture'] = $newFileName;
            }

            if ($newPassword !== '') {
                $sql .= ', password = :password';
                $params['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $conn->prepare($sql . ' WHERE id = :id')->execute($params);
            $conn->commit();

            if ($newFileName !== null && $oldPicturePath && is_file($oldPicturePath)) {
                @unlink($oldPicturePath);
            }

            $success = $newPassword !== ''
                ? 'Profile and password updated successfully.'
                : 'Profile updated successfully.';

            $loadUser->execute(['id' => $userId]);
            $user = $loadUser->fetch(PDO::FETCH_ASSOC);

            $_SESSION['fullname'] = (string) ($user['fullname'] ?? '');
            $_SESSION['username'] = (string) ($user['username'] ?? '');
            $_SESSION['email'] = (string) ($user['email'] ?? '');
            $_SESSION['role'] = (string) ($user['role'] ?? '');
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
        } catch (Throwable $exception) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            if ($newFileName !== null) {
                $newPath = profileFilePath($profileDirectory, $newFileName);
                if ($newPath && is_file($newPath)) {
                    @unlink($newPath);
                }
            }

            error_log('Settings update error: ' . $exception->getMessage());
            $error = 'Unable to update your profile.';
        }
    }
}

if (isset($_GET['picture_removed'])) {
    $success = 'Profile picture removed.';
    $loadUser->execute(['id' => $userId]);
    $user = $loadUser->fetch(PDO::FETCH_ASSOC);

    $_SESSION['fullname'] = (string) ($user['fullname'] ?? '');
    $_SESSION['username'] = (string) ($user['username'] ?? '');
    $_SESSION['email'] = (string) ($user['email'] ?? '');
    $_SESSION['role'] = (string) ($user['role'] ?? '');
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
}

$currentPath = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
$avatarUrl = !empty($user['profile_picture']) ? profileImageUrl((string) $user['profile_picture']) : null;
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
    <style>
        .avatar-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9eefc;
            color: #334155;
            font-size: 34px;
            font-weight: 700;
            border: 3px solid rgba(255,255,255,0.65);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
            flex-shrink: 0;
        }

        .avatar-circle img {
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
                    <i class="fa-solid fa-circle-check"></i> <?php echo e($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="settings-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>

                <div class="settings-panel">

                    <div class="settings-section">
                        <div class="settings-section-title">Profile Picture</div>
                        <div class="settings-section-desc">This is how others will recognize you across the system.</div>

                        <div class="avatar-block">
                            <div class="avatar-circle" id="avatarPreview">
                                <?php if ($avatarUrl !== null): ?>
                                    <img src="<?php echo e($avatarUrl); ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo e(strtoupper(substr((string) ($user['fullname'] ?? 'U'), 0, 1))); ?>
                                <?php endif; ?>
                            </div>

                            <div class="avatar-actions">
                                <div class="avatar-actions-buttons">
                                    <label for="profileImageInput" class="btn-upload">
                                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Choose Image
                                    </label>
                                    <input type="file" name="profile_image" id="profileImageInput" accept=".jpg,.jpeg,.png,.gif" style="display:none;">

                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <button type="submit" name="remove_picture" value="1" class="btn-remove">Remove</button>
                                    <?php endif; ?>
                                </div>
                                <div class="avatar-hint" id="selectedFileText">We support PNGs, JPEGs and GIFs under 5MB</div>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="settings-field">
                                <label>Full Name</label>
                                <input type="text" name="fullname" value="<?php echo e((string) ($user['fullname'] ?? '')); ?>" required>
                            </div>
                            <div class="settings-field">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo e((string) ($user['username'] ?? '')); ?>" required>
                            </div>
                        </div>

                        <div class="settings-field">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo e((string) ($user['email'] ?? '')); ?>" required>
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

    const togglePassword = document.getElementById('togglePassword');
    const passwordFields = document.getElementById('passwordFields');

    if (togglePassword && passwordFields) {
        togglePassword.addEventListener('click', () => {
            passwordFields.classList.toggle('show');
            togglePassword.textContent = passwordFields.classList.contains('show') ? 'Hide' : 'Change Password';
        });
    }

    const profileImageInput = document.getElementById('profileImageInput');
    const selectedFileText = document.getElementById('selectedFileText');
    const avatarPreview = document.getElementById('avatarPreview');

    if (profileImageInput && selectedFileText) {
        profileImageInput.addEventListener('change', () => {
            const file = profileImageInput.files && profileImageInput.files[0];

            selectedFileText.textContent = file
                ? `Selected: ${file.name}`
                : 'We support PNGs, JPEGs and GIFs under 5MB';

            if (file && avatarPreview) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Profile preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>
</body>
</html>