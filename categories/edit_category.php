<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db_connect.php';
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE categories SET name=:name WHERE id=:id");
    $stmt->execute(['name' => $_POST['name'], 'id' => $_POST['id']]);
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$cat = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Category</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f6f9; }
        .form-card { max-width: 500px; margin: 60px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 32px; }
        .form-control { border-radius: 8px; padding: 10px 14px; margin-bottom: 18px; }
        .btn-save { background: #2563eb; color: #fff; border-radius: 8px; padding: 10px 24px; border: none; }
        .btn-cancel { color: #64748b; text-decoration: none; padding: 10px 20px; }
    </style>
</head>
<body>
<div class="form-card">
    <h2><i class="fa-solid fa-pen me-2"></i>Edit Category</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
        <label class="fw-medium">Category Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
        <div class="d-flex justify-content-end mt-3">
            <a href="index.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-save">Update</button>
        </div>
    </form>
</div>
</body>
</html>