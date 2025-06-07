<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\service\draw\ColorChangeService;
use App\service\draw\FloodFillService;

header('Content-Type: application/json');
session_start();
apiRequireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if ($action === 'flood_fill') {
        $canvas = $input['canvas'];
        $x = $input['x'];
        $y = $input['y'];
        $targetColor = $input['targetColor'];
        $replacementColor = $input['replacementColor'];
        FloodFillService::floodFill($canvas, $x, $y, $targetColor, $replacementColor);
        echo json_encode(['canvas' => $canvas]);
        exit;
    }
    if ($action === 'change_color') {
        $canvas = $input['canvas'];
        $fromColor = $input['fromColor'];
        $toColor = $input['toColor'];
        ColorChangeService::changeColor($canvas, $fromColor, $toColor);
        echo json_encode(['canvas' => $canvas]);
        exit;
    }
}
echo json_encode(['error' => 'Invalid request']);