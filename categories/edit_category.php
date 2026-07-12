<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/db_connect.php';

requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$id) {
    redirectTo('index.php?error=invalid_category');
}

$stmt = $conn->prepare('SELECT id, name FROM categories WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrAbort();

    $name = trim((string) ($_POST['name'] ?? ''));

    if ($name === '' || mb_strlen($name) > 100) {
        $error = 'Category name is required and must not exceed 100 characters.';
    } else {
        $duplicate = $conn->prepare('SELECT id FROM categories WHERE name = :name AND id != :id LIMIT 1');
        $duplicate->execute([
            'name' => $name,
            'id' => $id,
        ]);

        if ($duplicate->fetch()) {
            $error = 'That category already exists.';
        } else {
            $conn->beginTransaction();

            try {
                $conn->prepare('UPDATE categories SET name = :name WHERE id = :id')
                    ->execute([
                        'name' => $name,
                        'id' => $id,
                    ]);

                $conn->prepare('UPDATE expenses SET category = :category, title = :title WHERE category = :old_name')
                    ->execute([
                        'category' => $name,
                        'title' => $name,
                        'old_name' => $category['name'],
                    ]);

                $conn->commit();
                redirectTo('index.php?success=updated');
            } catch (Throwable $exception) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                error_log('Category update error: ' . $exception->getMessage());
                $error = 'Unable to update the category.';
            }
        }
    }

    $category['name'] = $name;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<main class="container py-5">
    <div class="panel mx-auto" style="max-width:500px">
        <h1 class="h3 mb-3">Edit Category</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="post" action="edit_category.php?id=<?php echo (int) $id; ?>" novalidate>
            <?php echo csrfField(); ?>

            <label class="form-label" for="name">Category Name</label>
            <input id="name" class="form-control" name="name" maxlength="100" value="<?php echo e($category['name']); ?>" required>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="index.php" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary" type="submit">Update</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>