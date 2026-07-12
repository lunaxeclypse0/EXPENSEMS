<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $conn;
    private string $table = 'users';

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function findByUsername(string $username): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role
                FROM {$this->table}
                WHERE username = :username
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsernameOrEmail(string $identifier): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role
                FROM {$this->table}
                WHERE username = :identifier1 OR email = :identifier2
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':identifier1', $identifier, PDO::PARAM_STR);
        $stmt->bindValue(':identifier2', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function usernameExists(string $username): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function emailExists(string $email): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function createUser(
        string $fullname,
        string $username,
        string $email,
        string $hashedPassword,
        string $role = 'user'
    ): bool {
        $sql = "INSERT INTO {$this->table} (fullname, username, email, password, role)
                VALUES (:fullname, :username, :email, :password, :role)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function findById(int $userId): array|false
    {
        $stmt = $this->conn->prepare("
            SELECT id, fullname, username, email, password, google_id, profile_picture, role, created_at
            FROM {$this->table}
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePasswordHash(int $userId, string $passwordHash): bool
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET password = :password WHERE id = :id");
        $stmt->bindValue(':password', $passwordHash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function usernameOrEmailExistsForAnotherUser(string $username, string $email, int $userId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM {$this->table}
            WHERE (username = :username OR email = :email) AND id != :id
            LIMIT 1
        ");
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function findByGoogleId(string $googleId): array|false
    {
        $sql = "SELECT id, fullname, username, email, google_id, role
                FROM {$this->table}
                WHERE google_id = :google_id
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function linkGoogleId(int $userId, string $googleId): bool
    {
        $sql = "UPDATE {$this->table} SET google_id = :google_id WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function createGoogleUser(
        string $fullname,
        string $username,
        string $email,
        string $googleId,
        string $role = 'user'
    ): bool {
        $sql = "INSERT INTO {$this->table} (fullname, username, email, password, google_id, role)
                VALUES (:fullname, :username, :email, :password, :google_id, :role)";

        $stmt = $this->conn->prepare($sql);
        $password = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        $stmt->bindValue(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->bindValue(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);

        return $stmt->execute();
    }
}