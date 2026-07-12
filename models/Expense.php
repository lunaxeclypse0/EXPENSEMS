<?php
declare(strict_types=1);

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

    /** @return array{today: float, month: float, overall: float} */
    public function getDashboardTotals(?int $userId = null): array
    {
        $scope = $userId === null ? '' : ' WHERE user_id = :user_id';

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN expense_date = CURDATE() THEN amount ELSE 0 END), 0) AS today,
                    COALESCE(SUM(CASE
                        WHEN expense_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND expense_date < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
                        THEN amount ELSE 0 END), 0) AS month,
                    COALESCE(SUM(amount), 0) AS overall
                FROM {$this->table}{$scope}";

        $stmt = $this->conn->prepare($sql);

        if ($userId !== null) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'today' => (float) ($totals['today'] ?? 0),
            'month' => (float) ($totals['month'] ?? 0),
            'overall' => (float) ($totals['overall'] ?? 0),
        ];
    }

    public function getTotalToday(?int $userId = null): float
    {
        return $this->getDashboardTotals($userId)['today'];
    }

    public function getTotalThisMonth(?int $userId = null): float
    {
        return $this->getDashboardTotals($userId)['month'];
    }

    public function getTotalOverall(?int $userId = null): float
    {
        return $this->getDashboardTotals($userId)['overall'];
    }

    public function getRecent(?int $userId = null, int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));
        $scope = $userId === null ? '' : 'WHERE user_id = :user_id';

        $sql = "SELECT id, title, amount, expense_date
                FROM {$this->table}
                {$scope}
                ORDER BY expense_date DESC, id DESC
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);

        if ($userId !== null) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}