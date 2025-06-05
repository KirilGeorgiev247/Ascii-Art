<?php

namespace App\db\repository\friend;

use App\db\Database;
use App\model\Friend;
use PDO;

class FriendRepository
{
    public static function getFriends(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM friends WHERE (user_id = :user_id OR friend_id = :user_id) AND status = "accepted"'
        );
        $stmt->execute(['user_id' => $userId]);
        $friends = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $friends[] = Friend::fromRow($row);
        }
        return $friends;
    }

    public static function getPendingRequests(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM friends WHERE friend_id = :user_id AND status = "pending"'
        );
        $stmt->execute(['user_id' => $userId]);
        $requests = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $requests[] = Friend::fromRow($row);
        }
        return $requests;
    }

    public static function addFriend(int $userId, int $friendId): ?Friend
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id)'
        );
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
        if ($stmt->fetch()) {
            return null;
        }
        $stmt = $db->getConnection()->prepare(
            'INSERT INTO friends (user_id, friend_id, status, created_at) VALUES (:user_id, :friend_id, "pending", NOW())'
        );
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
        return self::getFriendship($userId, $friendId);
    }

    public static function acceptFriendRequest(int $userId, int $friendId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'UPDATE friends SET status = "accepted" WHERE user_id = :friend_id AND friend_id = :user_id AND status = "pending"'
        );
        return $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
    }

    public static function removeFriend(int $userId, int $friendId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'DELETE FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id)'
        );
        return $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
    }

    public static function getFriendship(int $userId, int $friendId): ?Friend
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id) LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Friend::fromRow($row) : null;
    }

    public static function areFriends(int $userId, int $friendId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM friends WHERE ((user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id)) AND status = "accepted"'
        );
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId]);
        return (bool)$stmt->fetch();
    }
}