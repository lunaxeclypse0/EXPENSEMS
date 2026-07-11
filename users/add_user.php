<?php
require_once '../config/database.php';
$database = new Database();
$conn = $database->connect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $check->execute(['email' => $email, 'username' => $username]);

    if ($check->fetch()) {
        $error = "Email or username already exists.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, role) VALUES (:fullname, :username, :email, :password, :role)");
        $stmt->execute([
            'fullname' => $fullname,
            'username' => $username,
            'email' => $email,
            'password' => $hashed,
            'role' => $role
        ]);

        $notifMsg = "New user added: " . $fullname . " (" . $role . ")";
        $notifStmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (:msg, 'user', '../users/index.php')");
        $notifStmt->execute(['msg' => $notifMsg]);

        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--bg-main);">
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="panel" style="width: 420px;">
        <h4 class="mb-4">Add New User</h4>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-add w-100" style="border:none;">Create User</button>
            <a href="index.php" class="d-block text-center mt-3 text-decoration-none">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>