<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private int $port;

    public ?PDO $conn = null;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->db_name = getenv('DB_DATABASE') ?: 'em-system';
        $this->username = getenv('DB_USERNAME') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->port = (int) (getenv('DB_PORT') ?: 3306);
    }

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