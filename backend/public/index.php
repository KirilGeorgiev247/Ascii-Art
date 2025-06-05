<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\db\Database;

try {
    $db = new Database();
    echo "<p> Database connection successful.</p>";
} catch (Exception $e) {
    echo "<p> Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<p> PHP server is running.</p>";
