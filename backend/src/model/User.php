<?php

namespace App\model;

use App\db\repository\like\LikeRepository;
use App\db\repository\user\UserRepository;
use App\service\logger\Logger;

class User
{
    private ?int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private ?string $profilePicture;
    private ?string $bio;
    private string $createdAt;

    public function __construct(?int $id, string $username, string $email, string $passwordHash, ?string $profilePicture = null, ?string $bio = null, string $createdAt = '')
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->profilePicture = $profilePicture;
        $this->bio = $bio;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }
    public function getBio(): ?string
    {
        return $this->bio;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
    public function setProfilePicture(?string $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
    }
    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }

    public function verifyPassword(string $password): bool
    {
        $logger = Logger::getInstance();
        $logger->debug("Password verification attempt", ['username' => $this->username]);
        $result = password_verify($password, $this->passwordHash);
        $logger->debug("Password verification result", [
            'username' => $this->username,
            'success' => $result
        ]);
        return $result;
    }

    public static function findById(int $id): ?self
    {
        $logger = Logger::getInstance();
        $logger->debug("Finding user by ID 123", ['id' => $id]);
        return UserRepository::findById($id);
    }

    public static function findByEmail(string $email): ?self
    {
        return UserRepository::findByEmail($email);
    }

    public static function findByUsername(string $username): ?self
    {
        return UserRepository::findByUsername($username);
    }

    public static function create(string $username, string $email, string $password): self
    {
        return UserRepository::create($username, $email, $password);
    }

    public function save(): bool
    {
        return UserRepository::save($this);
    }

    public function updatePassword(string $newPassword): bool
    {
        return UserRepository::updatePassword($this, $newPassword);
    }

    public static function authenticate(string $email, string $password): ?self
    {
        $user = self::findByEmail($email);
        if ($user && $user->verifyPassword($password)) {
            return $user;
        }
        return null;
    }

    public static function getAll(int $limit = 50): array
    {
        return UserRepository::getAll($limit);
    }

    public static function searchByUsername(string $query): array
    {
        return UserRepository::searchByUsername($query);
    }

    public static function getLikes(int $userId): int
    {
        return LikeRepository::countLikesReceivedByUser($userId);
    }
}