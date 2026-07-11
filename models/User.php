<?php
/**
 * User Model
 * Handles all database operations related to the users table
 */

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

    /**
     * Find a user by username
     */
    public function findByUsername(string $username): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role 
                FROM {$this->table} 
                WHERE username = :username 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role 
                FROM {$this->table} 
                WHERE email = :email 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Find a user by username OR email (used for login)
     */
    public function findByUsernameOrEmail(string $identifier): array|false
    {
        $sql = "SELECT id, fullname, username, email, password, role 
                FROM {$this->table} 
                WHERE username = :identifier1 OR email = :identifier2 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':identifier1', $identifier, PDO::PARAM_STR);
        $stmt->bindParam(':identifier2', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Check if a username already exists
     */
    public function usernameExists(string $username): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Check if an email already exists
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Create a new user (Register)
     */
    public function createUser(string $fullname, string $username, string $email, string $hashedPassword, string $role = 'staff'): bool
    {
        $sql = "INSERT INTO {$this->table} (fullname, username, email, password, role) 
                VALUES (:fullname, :username, :email, :password, :role)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Find a user by Google ID
     */
    public function findByGoogleId(string $googleId): array|false
    {
        $sql = "SELECT id, fullname, username, email, google_id, role 
                FROM {$this->table} 
                WHERE google_id = :google_id 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Link a Google ID to an existing account (matched by email)
     */
    public function linkGoogleId(int $userId, string $googleId): bool
    {
        $sql = "UPDATE {$this->table} SET google_id = :google_id WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Create a new user via Google Sign-In (no password needed)
     */
    public function createGoogleUser(string $fullname, string $username, string $email, string $googleId, string $role = 'staff'): bool
    {
        $sql = "INSERT INTO {$this->table} (fullname, username, email, google_id, role) 
                VALUES (:fullname, :username, :email, :google_id, :role)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':google_id', $googleId, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);

        return $stmt->execute();
    }
}