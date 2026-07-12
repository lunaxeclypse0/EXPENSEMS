<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireAdmin();

$values = [
    'fullname' => '',
    'username' => '',
    'email' => '',
    'role' => 'staff',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    foreach (array_keys($values) as $key) {
        if ($key !== 'role') {
            $values[$key] = trim((string) ($_POST[$key] ?? ''));
        }
    }

    $values['email'] = strtolower($values['email']);
    $values['role'] = (string) ($_POST['role'] ?? 'staff');
    $password = (string) ($_POST['password'] ?? '');

    if (
        $values['fullname'] === '' ||
        mb_strlen($values['fullname']) > 100 ||
        !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $values['username']) ||
        !filter_var($values['email'], FILTER_VALIDATE_EMAIL) ||
        !in_array($values['role'], ['admin', 'staff'], true) ||
        strlen($password) < 12
    ) {
        $error = 'Provide a full name, valid username and email, permitted role, and a password of at least 12 characters.';
    } else {
        $check = $conn->prepare('
            SELECT id
            FROM users
            WHERE email = :email OR username = :username
            LIMIT 1
        ');
        $check->execute([
            'email' => $values['email'],
            'username' => $values['username'],
        ]);

        if ($check->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Email or username already exists.';
        } else {
            $conn->beginTransaction();

            try {
                $stmt = $conn->prepare('
                    INSERT INTO users (fullname, username, email, password, role)
                    VALUES (:fullname, :username, :email, :password, :role)
                ');
                $stmt->execute([
                    'fullname' => $values['fullname'],
                    'username' => $values['username'],
                    'email' => $values['email'],
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $values['role'],
                ]);

                $newUserId = (int) $conn->lastInsertId();

                $notify = $conn->prepare('
                    INSERT INTO notifications (user_id, message, type, link)
                    VALUES (:user_id, :message, :type, :link)
                ');
                $notify->execute([
                    'user_id' => $newUserId,
                    'message' => 'Your account has been created.',
                    'type' => 'user',
                    'link' => '../settings/index.php',
                ]);

                $conn->commit();
                redirectTo('index.php?success=created');
            } catch (Throwable $exception) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                error_log('User creation error: ' . $exception->getMessage());
                $error = 'Unable to create the user.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--bg-main);">
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="panel" style="width: 420px;">
        <h4 class="mb-4">Add New User</h4>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <?php echo csrfField(); ?>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" maxlength="100" value="<?php echo e($values['fullname']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" maxlength="50" value="<?php echo e($values['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" maxlength="150" value="<?php echo e($values['email']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="12" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff" <?php echo $values['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="admin" <?php echo $values['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <button type="submit" class="btn-add w-100" style="border:none;">Create User</button>
            <a href="index.php" class="d-block text-center mt-3 text-decoration-none">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>