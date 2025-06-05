<?php

namespace App\Model;

use App\Config\Config;
use PDO;

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private string $createdAt;

    /**
     * User constructor.
     */
    public function __construct(?int $id, string $username, string $email, string $passwordHash, string $createdAt)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * Find a user by ID.
     */
    public static function findById(int $id): ?self
    {
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new self(
                (int)$row['id'], 
                $row['username'], 
                $row['email'], 
                $row['password_hash'], 
                $row['created_at']
            );
        }

        return null;
    }

    /**
     * Find a user by email.
     */
    public static function findByEmail(string $email): ?self
    {
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, created_at FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new self(
                (int)$row['id'], 
                $row['username'], 
                $row['email'], 
                $row['password_hash'], 
                $row['created_at']
            );
        }

        return null;
    }

    /**
     * Register a new user.
     */
    public static function create(string $username, string $email, string $password): self
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, created_at) VALUES (:username, :email, :password_hash, :created_at)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'created_at' => $createdAt
        ]);

        $id = (int)$pdo->lastInsertId();

        return new self($id, $username, $email, $passwordHash, $createdAt);
    }

    /**
     * Update existing user record.
     */
    public function save(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'UPDATE users SET username = :username, email = :email WHERE id = :id'
        );

        return $stmt->execute([
            'username' => $this->username,
            'email' => $this->email,
            'id' => $this->id
        ]);
    }

    /**
     * Delete user record.
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Config::getPDO();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $this->id]);
    }

    /**
     * Authenticate a user.
     */
    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::findByEmail($email);
        if ($user && $user->verifyPassword($password)) {
            return $user;
        }

        return null;
    }
}
