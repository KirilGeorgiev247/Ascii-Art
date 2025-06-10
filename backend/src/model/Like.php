<?php

namespace App\model;

use App\db\repository\like\LikeRepository;

class Like
{
    private ?int $id;
    private int $userId;
    private int $postId;
    private string $createdAt;
    private ?string $username;

    public function __construct(
        ?int $id,
        int $userId,
        int $postId,
        string $createdAt = '',
        ?string $username = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->postId = $postId;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->username = $username;
    }

    public static function isPostLikedByUser(int $userId, int $postId): bool
    {
        $repository = new LikeRepository();
        return $repository->isPostLikedByUser($userId, $postId);
    }

    public static function likePost(int $userId, int $postId): bool
    {
        $repository = new LikeRepository();
        return $repository->likePost($userId, $postId);
    }

    public static function unlikePost(int $userId, int $postId): bool
    {
        $repository = new LikeRepository();
        return $repository->unlikePost($userId, $postId);
    }

    public static function getLikesForPost(int $postId): array
    {
        $repository = new LikeRepository();
        return $repository->getLikesForPost($postId);
    }
}