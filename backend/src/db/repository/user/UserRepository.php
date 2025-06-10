<?php

namespace App\db\repository\user;

use App\db\Database;
use App\model\User;
use PDO;

class UserRepository
{
    public static function findById(int $id): ?User
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return self::rowToUser($row);
        }
        return null;
    }

    public static function findByEmail(string $email): ?User
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return self::rowToUser($row);
        }
        return null;
    }

    public static function create(string $username, string $email, string $password): User
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $createdAt = date('Y-m-d H:i:s');

        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'INSERT INTO users (username, email, password_hash, created_at) VALUES (:username, :email, :password_hash, :created_at)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'created_at' => $createdAt
        ]);

        $id = (int) $db->getConnection()->lastInsertId();
        return new User($id, $username, $email, $passwordHash, null, null, $createdAt);
    }

    public static function save(User $user): bool
    {
        if ($user->getId() === null) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'UPDATE users SET username = :username, email = :email, profile_picture = :profile_picture, bio = :bio WHERE id = :id'
        );

        return $stmt->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'profile_picture' => $user->getProfilePicture(),
            'bio' => $user->getBio(),
            'id' => $user->getId()
        ]);
    }

    public static function searchByUsername(string $query): array
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM users WHERE username LIKE :query ORDER BY username LIMIT 20'
        );
        $stmt->execute(['query' => '%' . $query . '%']);

        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = self::rowToUser($row);
        }
        return $users;
    }

    private static function rowToUser(array $row): User
    {
        return new User(
            (int) $row['id'],
            $row['username'],
            $row['email'],
            $row['password_hash'],
            $row['profile_picture'] ?? null,
            $row['bio'] ?? null,
            $row['created_at']
        );
    }
}