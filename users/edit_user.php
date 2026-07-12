<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$id) {
    redirectTo('index.php?error=invalid_user');
}

$find = $conn->prepare('
    SELECT id, fullname, username, email, role
    FROM users
    WHERE id = :id
    LIMIT 1
');
$find->execute(['id' => $id]);
$user = $find->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirectTo('index.php?error=user_not_found');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $role = (string) ($_POST['role'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (
        $fullname === '' ||
        mb_strlen($fullname) > 100 ||
        !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !in_array($role, ['admin', 'staff'], true) ||
        ($password !== '' && strlen($password) < 12)
    ) {
        $error = 'Please provide valid user details. New passwords must be at least 12 characters.';
    } elseif ((int) $id === currentUserId() && $role !== 'admin') {
        $error = 'You cannot remove your own administrator role.';
    } else {
        $check = $conn->prepare('
            SELECT id
            FROM users
            WHERE (email = :email OR username = :username) AND id != :id
            LIMIT 1
        ');
        $check->execute([
            'email' => $email,
            'username' => $username,
            'id' => $id,
        ]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Email or username already belongs to another account.';
        } else {
            $sql = 'UPDATE users SET fullname = :fullname, username = :username, email = :email, role = :role';
            $params = [
                'fullname' => $fullname,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'id' => $id,
            ];

            if ($password !== '') {
                $sql .= ', password = :password';
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $conn->prepare($sql . ' WHERE id = :id')->execute($params);

            if ((int) $id === currentUserId()) {
                $_SESSION['fullname'] = $fullname;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
            }

            redirectTo('index.php?success=updated');
        }
    }

    $user = [
        'id' => $id,
        'fullname' => $fullname,
        'username' => $username,
        'email' => $email,
        'role' => $role,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--bg-main);">
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="panel" style="width: 420px;">
        <h4 class="mb-4">Edit User</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_user.php?id=<?php echo (int) $id; ?>" novalidate>
            <?php echo csrfField(); ?>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" maxlength="100" value="<?php echo e($user['fullname']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" maxlength="50" value="<?php echo e($user['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="150" value="<?php echo e($user['email']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control" minlength="12">
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn-add w-100" style="border:none;">Save Changes</button>
            <a href="index.php" class="d-block text-center mt-3 text-decoration-none">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>