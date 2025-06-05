<?php

namespace App\Model;

use App\Config\Config;
use PDO;

class Post
{
    private ?int $id;
    private int $userId;
    private string $content;
    private string $createdAt;

    /**
     * Post constructor.
     */
    public function __construct(?int $id, int $userId, string $content, string $createdAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->content = $content;
        $this->createdAt = $createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Find a post by ID.
     *
     * @return Post|null
     */
    public static function findById(int $id): ?self
    {
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'SELECT id, user_id, content, created_at FROM posts WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new self(
                (int)$row['id'], 
                (int)$row['user_id'], 
                $row['content'], 
                $row['created_at']
            );
        }

        return null;
    }

    /**
     * Fetch recent posts for the feed.
     *
     * @param int $limit
     * @return Post[]
     */
    public static function fetchRecent(int $limit = 20): array
    {
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'SELECT id, user_id, content, created_at FROM posts ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $posts = [];
        foreach ($rows as $row) {
            $posts[] = new self(
                (int)$row['id'], 
                (int)$row['user_id'], 
                $row['content'], 
                $row['created_at']
            );
        }

        return $posts;
    }

    /**
     * Fetch all posts by a given user.
     *
     * @param int $userId
     * @return Post[]
     */
    public static function findByUserId(int $userId): array
    {
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'SELECT id, user_id, content, created_at FROM posts WHERE user_id = :user_id ORDER BY created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $posts = [];
        foreach ($rows as $row) {
            $posts[] = new self(
                (int)$row['id'], 
                (int)$row['user_id'], 
                $row['content'], 
                $row['created_at']
            );
        }

        return $posts;
    }

    /**
     * Create a new post.
     *
     * @return Post
     */
    public static function create(int $userId, string $content): self
    {
        $createdAt = date('Y-m-d H:i:s');
        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, content, created_at) VALUES (:user_id, :content, :created_at)'
        );
        $stmt->execute([
            'user_id'    => $userId,
            'content'    => $content,
            'created_at' => $createdAt
        ]);

        $id = (int)$pdo->lastInsertId();
        return new self($id, $userId, $content, $createdAt);
    }

    /**
     * Update an existing post.
     */
    public function save(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Config::getPDO();
        $stmt = $pdo->prepare(
            'UPDATE posts SET content = :content WHERE id = :id'
        );

        return $stmt->execute([
            'content' => $this->content,
            'id'      => $this->id
        ]);
    }

    /**
     * Delete a post.
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo = Config::getPDO();
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute(['id' => $this->id]);
    }
}
