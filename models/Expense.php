<?php
require_once __DIR__ . '/../config/database.php';

class Expense
{
    private PDO $conn;
    private string $table = 'expenses';

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getTotalToday(): float
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM {$this->table} WHERE expense_date = CURDATE()";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return (float) $stmt->fetch()['total'];
    }

    public function getTotalThisMonth(): float
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM {$this->table} 
                WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return (float) $stmt->fetch()['total'];
    }

    public function getTotalOverall(): float
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return (float) $stmt->fetch()['total'];
    }

    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT id, title, amount, expense_date FROM {$this->table} 
                ORDER BY expense_date DESC, id DESC LIMIT :limit";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}