<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private string $host = '127.0.0.1';
    private string $db_name = 'em-system';
    private string $username = 'root';
    private string $password = '';
    private int $port = 3306;

    public ?PDO $conn = null;

    public function connect(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please contact the administrator.');
        }

        return $this->conn;
    }
}