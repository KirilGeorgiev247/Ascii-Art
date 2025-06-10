<?php

namespace App\controller;

use App\model\Post;

class DrawController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $title = "Draw - ASCII Art Social Network";
        require_once dirname(dirname(__DIR__)) . '/views/draw/draw.php';
    }

    public function save()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }
        $userId = $_SESSION['user_id'];
        $title = $_POST['title'] ?? 'Untitled Drawing';
        $asciiContent = $_POST['ascii_content'] ?? '';
        $imagePath = $_POST['image_path'] ?? null;

        $post = Post::create(
            $userId,
            $title,
            $asciiContent,
            'drawing',
            $imagePath,
            $asciiContent,
            'public'
        );
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'post_id' => $post->getId()]);
    }
}