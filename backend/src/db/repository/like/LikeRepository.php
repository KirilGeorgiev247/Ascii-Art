<?php

namespace App\db\repository\like;

use App\db\Database;
use App\model\Like;
use App\service\logger\Logger;
use PDO;
use Exception;

class LikeRepository
{
    public static function isPostLikedByUser(int $userId, int $postId): bool
    {
        $logger = Logger::getInstance();
        $logger->debug("Checking if post is liked by user", [
            'user_id' => $userId,
            'post_id' => $postId
        ]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id'
            );
            $stmt->execute(['user_id' => $userId, 'post_id' => $postId]);

            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            $logger->logException($e, 'Error checking if post is liked');
            return false;
        }
    }

    public static function likePost(int $userId, int $postId): bool
    {
        $logger = Logger::getInstance();
        $logger->info("Liking post", [
            'user_id' => $userId,
            'post_id' => $postId
        ]);

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Begin transaction
            $conn->beginTransaction();

            // Insert like record
            $stmt = $conn->prepare(
                'INSERT INTO likes (user_id, post_id, created_at) VALUES (:user_id, :post_id, :created_at)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'post_id' => $postId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update post likes count
            $stmt = $conn->prepare(
                'UPDATE posts SET likes_count = likes_count + 1 WHERE id = :post_id'
            );
            $stmt->execute(['post_id' => $postId]);

            // Commit transaction
            $conn->commit();

            $logger->info("Post liked successfully", [
                'user_id' => $userId,
                'post_id' => $postId
            ]);

            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }

            $logger->logException($e, 'Error liking post');
            return false;
        }
    }

    public static function unlikePost(int $userId, int $postId): bool
    {
        $logger = Logger::getInstance();
        $logger->info("Unliking post", [
            'user_id' => $userId,
            'post_id' => $postId
        ]);

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            // Begin transaction
            $conn->beginTransaction();

            // Delete like record
            $stmt = $conn->prepare(
                'DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id'
            );
            $stmt->execute(['user_id' => $userId, 'post_id' => $postId]);

            // Update post likes count
            $stmt = $conn->prepare(
                'UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = :post_id'
            );
            $stmt->execute(['post_id' => $postId]);

            // Commit transaction
            $conn->commit();

            $logger->info("Post unliked successfully", [
                'user_id' => $userId,
                'post_id' => $postId
            ]);

            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }

            $logger->logException($e, 'Error unliking post');
            return false;
        }
    }

    public static function getLikesForPost(int $postId): array
    {
        $logger = Logger::getInstance();
        $logger->debug("Getting likes for post", ['post_id' => $postId]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT l.*, u.username FROM likes l 
                 JOIN users u ON l.user_id = u.id 
                 WHERE l.post_id = :post_id 
                 ORDER BY l.created_at DESC'
            );
            $stmt->execute(['post_id' => $postId]);

            $likes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $likes[] = self::rowToLike($row);
            }

            return $likes;
        } catch (Exception $e) {
            $logger->logException($e, 'Error getting likes for post');
            return [];
        }
    }

    public static function findById(int $id): ?Like
    {
        $logger = Logger::getInstance();
        $logger->debug("Finding like by ID", ['like_id' => $id]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare('SELECT * FROM likes WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return self::rowToLike($row);
            }
            return null;
        } catch (Exception $e) {
            $logger->logException($e, 'Error finding like by ID');
            return null;
        }
    }

    public static function countLikesReceivedByUser(int $userId): int
    {
        $logger = Logger::getInstance();
        $logger->debug("Counting likes received by user", ['user_id' => $userId]);
        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT COUNT(*) as count FROM likes l
             JOIN posts p ON l.post_id = p.id
             WHERE p.user_id = :user_id'
            );
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['count'] ?? 0);
        } catch (Exception $e) {
            $logger->logException($e, 'Error counting likes received by user');
            return 0;
        }
    }

    public static function countLikesForPost(int $postId): int
    {
        $logger = Logger::getInstance();
        $logger->debug("Counting likes for post", ['post_id' => $postId]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id'
            );
            $stmt->execute(['post_id' => $postId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($row['count'] ?? 0);
        } catch (Exception $e) {
            $logger->logException($e, 'Error counting likes for post');
            return 0;
        }
    }

    public static function deleteLikesForPost(int $postId): bool
    {
        $logger = Logger::getInstance();
        $logger->info("Deleting all likes for post", ['post_id' => $postId]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'DELETE FROM likes WHERE post_id = :post_id'
            );
            return $stmt->execute(['post_id' => $postId]);
        } catch (Exception $e) {
            $logger->logException($e, 'Error deleting likes for post');
            return false;
        }
    }

    private static function rowToLike(array $row): Like
    {
        return new Like(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['post_id'],
            $row['created_at'],
            $row['username'] ?? null
        );
    }
}