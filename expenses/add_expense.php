<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db_connect.php';

$catStmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
$catStmt->execute();
$categories = $catStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense_date   = $_POST['expense_date'];
    $category       = $_POST['category'];
    $description    = $_POST['description'];
    $amount         = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $remarks        = $_POST['remarks'];
    $user_id        = 1;
    $title          = $category;

    $stmt = $conn->prepare("INSERT INTO expenses (user_id, title, expense_date, category, description, amount, payment_method, remarks) VALUES (:user_id, :title, :expense_date, :category, :description, :amount, :payment_method, :remarks)");
    $stmt->execute([
        'user_id' => $user_id,
        'title' => $title,
        'expense_date' => $expense_date,
        'category' => $category,
        'description' => $description,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'remarks' => $remarks,
    ]);

    $notifMsg = "New expense added: " . $description . " - ₱" . number_format($amount, 2);
    $notifStmt = $conn->prepare("INSERT INTO notifications (message, type, link) VALUES (:msg, 'expense', '../expenses/index.php')");
    $notifStmt->execute(['msg' => $notifMsg]);

    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f6f9; }
        .form-card { max-width: 640px; margin: 50px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 32px; }
        .form-card h2 { font-weight: 600; color: #1e2a3a; margin-bottom: 4px; }
        .form-card p.sub { color: #64748b; margin-bottom: 24px; }
        label { font-weight: 500; color: #334155; margin-bottom: 6px; }
        .form-control, .form-select { border-radius: 8px; padding: 10px 14px; border: 1px solid #dfe3ea; margin-bottom: 18px; }
        .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-save { background: #2563eb; color: #fff; border-radius: 8px; padding: 10px 24px; font-weight: 500; border: none; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel { color: #64748b; text-decoration: none; padding: 10px 20px; font-weight: 500; }
        .manage-link { font-size: 13px; color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
<div class="form-card">
    <h2><i class="fa-solid fa-plus me-2"></i>Add New Expense</h2>
    <p class="sub">Fill in the details below to record a new expense.</p>
    <form method="POST" action="add_expense.php">
        <label>Expense Date</label>
        <input type="date" class="form-control" name="expense_date" required>

        <div class="d-flex justify-content-between align-items-center">
            <label class="mb-0">Category</label>
            <a href="../categories/index.php" class="manage-link">Manage Categories</a>
        </div>
        <select class="form-select" name="category" required>
            <option value="" disabled selected>Select a category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Description</label>
        <textarea class="form-control" name="description" rows="3" placeholder="Brief details about this expense" required></textarea>

        <label>Amount (₱)</label>
        <input type="number" step="0.01" class="form-control" name="amount" placeholder="0.00" required>

        <label>Payment Method</label>
        <input type="text" class="form-control" name="payment_method" placeholder="e.g. Cash, GCash, Bank Transfer" required>

        <label>Remarks</label>
        <textarea class="form-control" name="remarks" rows="2" placeholder="Optional notes"></textarea>

        <div class="d-flex justify-content-end mt-3">
            <a href="index.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-save">Save Expense</button>
        </div>
    </form>
</div>
</body>
</html>