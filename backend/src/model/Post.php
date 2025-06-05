<?php

namespace App\model;

use App\db\repository\post\PostRepository;

class Post
{
    private ?int $id;
    private int $userId;
    private string $title;
    private string $content;
    private string $type;
    private ?string $imagePath;
    private ?string $asciiContent;
    private string $visibility;
    private int $likesCount;
    private string $createdAt;
    private string $updatedAt;
    private ?string $username;

    public function __construct(
        ?int $id,
        int $userId,
        string $title,
        string $content,
        string $type = 'ascii_art',
        ?string $imagePath = null,
        ?string $asciiContent = null,
        string $visibility = 'public',
        int $likesCount = 0,
        string $createdAt = '',
        string $updatedAt = '',
        ?string $username = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->imagePath = $imagePath;
        $this->asciiContent = $asciiContent;
        $this->visibility = $visibility;
        $this->likesCount = $likesCount;
        $this->createdAt = $createdAt ?: date('Y-m-d H:i:s');
        $this->updatedAt = $updatedAt ?: date('Y-m-d H:i:s');
        $this->username = $username;
    }

    public function getId(): ?int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): string { return $this->type; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function getAsciiContent(): ?string { return $this->asciiContent; }
    public function getVisibility(): string { return $this->visibility; }
    public function getLikesCount(): int { return $this->likesCount; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    public function getUsername(): ?string { return $this->username; }

    public function setTitle(string $title): void { $this->title = $title; }
    public function setContent(string $content): void { $this->content = $content; }
    public function setType(string $type): void { $this->type = $type; }
    public function setImagePath(?string $imagePath): void { $this->imagePath = $imagePath; }
    public function setAsciiContent(?string $asciiContent): void { $this->asciiContent = $asciiContent; }
    public function setVisibility(string $visibility): void { $this->visibility = $visibility; }
    public function setUsername(?string $username): void { $this->username = $username; }

    public static function findById(int $id): ?self
    {
        return PostRepository::findById($id);
    }

    public static function fetchRecent(int $limit = 20): array
    {
        return PostRepository::fetchRecent($limit);
    }

    public static function findByUserId(int $userId): array
    {
        return PostRepository::findByUserId($userId);
    }

    public static function getFeedForUser(int $userId, int $limit = 20): array
    {
        return PostRepository::getFeedForUser($userId, $limit);
    }

    public static function create(
        int $userId,
        string $title,
        string $content,
        string $type = 'ascii_art',
        ?string $imagePath = null,
        ?string $asciiContent = null,
        string $visibility = 'public'
    ): self {
        return PostRepository::create($userId, $title, $content, $type, $imagePath, $asciiContent, $visibility);
    }

    public function save(): bool
    {
        return PostRepository::save($this);
    }

    public function delete(): bool
    {
        return PostRepository::delete($this);
    }

    public function incrementLikes(): bool
    {
        return PostRepository::incrementLikes($this);
    }
}