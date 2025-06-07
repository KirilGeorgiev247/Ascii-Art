<?php

namespace App\model;

use App\db\repository\friend\FriendRepository;

class Friend
{
    private ?int $id;
    private int $userId;
    private int $friendId;
    private string $status;
    private string $createdAt;

    public function __construct(?int $id, int $userId, int $friendId, string $status = 'pending', string $createdAt = '')
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->friendId = $friendId;
        $this->status = $status;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getFriendId(): int
    {
        return $this->friendId;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public static function fromRow(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            isset($row['user_id']) ? (int) $row['user_id'] : (int) $row['from_user_id'],
            isset($row['friend_id']) ? (int) $row['friend_id'] : (int) $row['to_user_id'],
            $row['status'] ?? 'pending',
            $row['created_at'] ?? ''
        );
    }

    public static function addFriend(int $userId, int $friendId): ?self
    {
        return FriendRepository::addFriend($userId, $friendId);
    }

    public static function getFriendship(int $userId, int $friendId): ?self
    {
        return FriendRepository::getFriendship($userId, $friendId);
    }

    public static function acceptFriendship(int $userId, int $friendId): bool
    {
        return FriendRepository::acceptFriendRequest($userId, $friendId);
    }

    public static function removeFriendship(int $userId, int $friendId): bool
    {
        return FriendRepository::removeFriend($userId, $friendId);
    }

    public static function getFriends(int $userId): array
    {
        return FriendRepository::getFriends($userId);
    }

    public static function getPendingRequests(int $userId): array
    {
        return FriendRepository::getPendingRequests($userId);
    }

    public static function areFriends(int $userId, int $friendId): bool
    {
        return FriendRepository::areFriends($userId, $friendId);
    }

    public static function getFriendshipStatus(int $userId, int $friendId): string
    {
        $friendship = self::getFriendship($userId, $friendId);

        if (!$friendship) {
            return 'none';
        }

        return $friendship->getStatus();
    }
}