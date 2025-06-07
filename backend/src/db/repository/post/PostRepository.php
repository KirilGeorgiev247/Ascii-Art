<?php

namespace App\db\repository\post;

use App\db\Database;
use App\model\Post;
use App\service\logger\Logger;
use PDO;
use Exception;

class PostRepository
{
    public static function findById(int $id): ?Post
    {
        $logger = Logger::getInstance();
        $logger->debug("Finding post by ID", ['post_id' => $id]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare('SELECT * FROM posts WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return self::rowToPost($row);
            }
            $logger->warning("Post not found", ['post_id' => $id]);
            return null;
        } catch (Exception $e) {
            $logger->logException($e, 'Error finding post by ID');
            return null;
        }
    }

    public static function fetchRecent(int $limit = 20): array
    {
        $logger = Logger::getInstance();
        $logger->debug("Fetching recent posts", ['limit' => $limit]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT p.*, u.username FROM posts p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE p.visibility = "public" 
                 ORDER BY p.created_at DESC LIMIT :limit'
            );
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $posts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $post = self::rowToPost($row);
                $post->setUsername($row['username']);
                $posts[] = $post;
            }
            return $posts;
        } catch (Exception $e) {
            $logger->logException($e, 'Error fetching recent posts');
            return [];
        }
    }

    public static function findByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->getConnection()->prepare(
            'SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $posts[] = self::rowToPost($row);
        }
        return $posts;
    }

    public static function getFeedForUser(int $userId, int $limit = 20): array
    {
        $logger = Logger::getInstance();
        $logger->debug("Fetching personalized feed for user", [
            'user_id' => $userId,
            'limit' => $limit
        ]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT p.*, u.username FROM posts p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE (p.user_id = :user_id OR 
                       p.user_id IN (SELECT friend_id FROM friends WHERE user_id = :user_id AND status = "accepted") OR
                       p.visibility = "public")
                 ORDER BY p.created_at DESC LIMIT :limit'
            );
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $posts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $post = self::rowToPost($row);
                $post->setUsername($row['username']);
                $posts[] = $post;
            }
            return $posts;
        } catch (Exception $e) {
            $logger->logException($e, 'Error fetching personalized feed');
            return [];
        }
    }

    public static function create(
        int $userId,
        string $title,
        string $content,
        string $type = 'ascii_art',
        ?string $imagePath = null,
        ?string $asciiContent = null,
        string $visibility = 'public'
    ): Post {
        $logger = Logger::getInstance();
        $logger->info("Creating new post", [
            'user_id' => $userId,
            'title' => $title,
            'content_length' => strlen($content),
            'ascii_content_length' => $asciiContent ? strlen($asciiContent) : 0,
            'type' => $type,
            'visibility' => $visibility,
            'has_image' => $imagePath !== null
        ]);

        try {
            $createdAt = date('Y-m-d H:i:s');
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'INSERT INTO posts (user_id, title, content, type, image_path, ascii_content, visibility, created_at, updated_at) 
                 VALUES (:user_id, :title, :content, :type, :image_path, :ascii_content, :visibility, :created_at, :updated_at)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'image_path' => $imagePath,
                'ascii_content' => $asciiContent,
                'visibility' => $visibility,
                'created_at' => $createdAt,
                'updated_at' => $createdAt
            ]);

            $id = (int) $db->getConnection()->lastInsertId();
            return new Post($id, $userId, $title, $content, $type, $imagePath, $asciiContent, $visibility, 0, $createdAt, $createdAt);
        } catch (Exception $e) {
            $logger->logException($e, 'Post creation failed');
            throw $e;
        }
    }

    public static function save(Post $post): bool
    {
        if ($post->getId() === null) {
            $logger = Logger::getInstance();
            $logger->error("Attempted to save post with null ID");
            return false;
        }

        $logger = Logger::getInstance();
        $logger->info("Saving post changes", [
            'post_id' => $post->getId(),
            'title' => $post->getTitle(),
            'content_length' => strlen($post->getContent()),
            'type' => $post->getType(),
            'visibility' => $post->getVisibility()
        ]);

        try {
            $updatedAt = date('Y-m-d H:i:s');
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'UPDATE posts SET title = :title, content = :content, type = :type, image_path = :image_path, 
                 ascii_content = :ascii_content, visibility = :visibility, updated_at = :updated_at WHERE id = :id'
            );

            $result = $stmt->execute([
                'title' => $post->getTitle(),
                'content' => $post->getContent(),
                'type' => $post->getType(),
                'image_path' => $post->getImagePath(),
                'ascii_content' => $post->getAsciiContent(),
                'visibility' => $post->getVisibility(),
                'updated_at' => $updatedAt,
                'id' => $post->getId()
            ]);

            if ($result) {
                // Update the object's updatedAt property
                $post->setUpdatedAt($updatedAt);
            }

            return $result;
        } catch (Exception $e) {
            $logger->logException($e, 'Post save operation failed');
            return false;
        }
    }

    public static function delete(Post $post): bool
    {
        if ($post->getId() === null) {
            $logger = Logger::getInstance();
            $logger->error("Attempted to delete post with null ID");
            return false;
        }

        $logger = Logger::getInstance();
        $logger->info("Deleting post", [
            'post_id' => $post->getId(),
            'title' => $post->getTitle(),
            'user_id' => $post->getUserId(),
            'type' => $post->getType()
        ]);

        try {
            $db = Database::getInstance();

            // Delete related likes first
            $stmt = $db->getConnection()->prepare('DELETE FROM likes WHERE post_id = :id');
            $stmt->execute(['id' => $post->getId()]);

            // Delete the post
            $stmt = $db->getConnection()->prepare('DELETE FROM posts WHERE id = :id');
            $result = $stmt->execute(['id' => $post->getId()]);

            return $result;
        } catch (Exception $e) {
            $logger->logException($e, 'Post deletion failed');
            return false;
        }
    }

    public static function incrementLikes(Post $post): bool
    {
        if ($post->getId() === null) {
            $logger = Logger::getInstance();
            $logger->error("Attempted to increment likes for post with null ID");
            return false;
        }

        $logger = Logger::getInstance();
        $logger->debug("Incrementing likes for post", [
            'post_id' => $post->getId(),
            'current_likes' => $post->getLikesCount()
        ]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare('UPDATE posts SET likes_count = likes_count + 1 WHERE id = :id');
            $success = $stmt->execute(['id' => $post->getId()]);

            if ($success) {
                // Update the object's likesCount property
                $reflection = new \ReflectionClass($post);
                $property = $reflection->getProperty('likesCount');
                $property->setAccessible(true);
                $property->setValue($post, $post->getLikesCount() + 1);
            }

            return $success;
        } catch (Exception $e) {
            $logger->logException($e, 'Error incrementing post likes');
            return false;
        }
    }

    private static function rowToPost(array $row): Post
    {
        return new Post(
            (int) $row['id'],
            (int) $row['user_id'],
            $row['title'],
            $row['content'],
            $row['type'],
            $row['image_path'] ?? null,
            $row['ascii_content'] ?? null,
            $row['visibility'],
            (int) $row['likes_count'],
            $row['created_at'],
            $row['updated_at'],
            $row['username'] ?? null
        );
    }

    public static function searchByQuery(string $query, int $limit = 50): array
    {
        $logger = Logger::getInstance();
        $logger->debug("Searching posts by query", [
            'query' => $query,
            'limit' => $limit
        ]);

        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'SELECT p.*, u.username FROM posts p 
             JOIN users u ON p.user_id = u.id 
             WHERE (p.title LIKE :query OR p.content LIKE :query OR p.ascii_content LIKE :query) 
             AND p.visibility = "public" 
             ORDER BY p.created_at DESC LIMIT :limit'
            );
            $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $posts = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $post = self::rowToPost($row);
                $post->setUsername($row['username']);
                $posts[] = $post;
            }
            return $posts;
        } catch (Exception $e) {
            $logger->logException($e, 'Error searching posts');
            return [];
        }
    }
}