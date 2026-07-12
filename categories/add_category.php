<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$name = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $name = trim((string) ($_POST['name'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        $error = 'Category name is required and must not exceed 100 characters.';
    } else {
        $duplicate = $conn->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $duplicate->execute(['name' => $name]);

        if ($duplicate->fetch()) {
            $error = 'That category already exists.';
        } else {
            $conn->prepare('INSERT INTO categories (name) VALUES (:name)')
                ->execute(['name' => $name]);

            redirectTo('index.php?success=created');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category | Expense Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<main class="container py-5">
    <div class="panel mx-auto" style="max-width:500px">
        <h1 class="h3 mb-3">Add Category</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php echo csrfField(); ?>

            <label class="form-label" for="name">Category Name</label>
            <input id="name" type="text" name="name" class="form-control" maxlength="100" value="<?php echo e($name); ?>" required>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="index.php" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>