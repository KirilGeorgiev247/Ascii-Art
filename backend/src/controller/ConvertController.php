<?php

namespace App\controller;

use App\model\Post;

class ConvertController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $title = "Convert Image to ASCII Art";
        require_once dirname(dirname(__DIR__)) . '/views/convert/convert.php';
    }

    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }
        $userId = $_SESSION['user_id'];
        $title = $_POST['title'] ?? 'Converted ASCII Art';
        $asciiContent = $_POST['ascii_content'] ?? '';
        $imagePath = $_POST['image_path'] ?? null;

        $post = Post::create(
            $userId,
            $title,
            $asciiContent,
            'ascii_art', // type
            $imagePath,
            $asciiContent,
            'public'
        );
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'post_id' => $post->getId()]);
    }
}