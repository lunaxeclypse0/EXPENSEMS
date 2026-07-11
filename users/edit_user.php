<?php
require_once '../config/database.php';
$database = new Database();
$conn = $database->connect();

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch();

if (!$user) { header("Location: index.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $check = $conn->prepare("SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id");
    $check->execute(['email' => $email, 'username' => $username, 'id' => $id]);

    if ($check->fetch()) {
        $error = "Email or username already used by another account.";
    } else {
        if (!empty($_POST['password'])) {
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET fullname=:fullname, username=:username, email=:email, role=:role, password=:password WHERE id=:id");
            $stmt->execute(['fullname'=>$fullname,'username'=>$username,'email'=>$email,'role'=>$role,'password'=>$hashed,'id'=>$id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=:fullname, username=:username, email=:email, role=:role WHERE id=:id");
            $stmt->execute(['fullname'=>$fullname,'username'=>$username,'email'=>$email,'role'=>$role,'id'=>$id]);
        }
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
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body style="background: var(--bg-main);">
<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="panel" style="width: 420px;">
        <h4 class="mb-4">Edit User</h4>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="staff" <?php echo $user['role']=='staff'?'selected':''; ?>>Staff</option>
                    <option value="admin" <?php echo $user['role']=='admin'?'selected':''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-add w-100" style="border:none;">Save Changes</button>
            <a href="index.php" class="d-block text-center mt-3 text-decoration-none">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>