<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../routes/web.php';

use App\service\convert\SobelEdgeService;
use App\service\convert\ColorReduceService;
use App\service\convert\SymbolReduceService;
use App\service\convert\ThresholdService;

header('Content-Type: application/json');
session_start();
apiRequireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (!isset($_FILES['image']) && !isset($_POST['image'])) {
        echo json_encode(['error' => 'No image uploaded']);
        exit;
    }

    if (isset($_FILES['image'])) {
        $imgPath = $_FILES['image']['tmp_name'];
        $img = imagecreatefromstring(file_get_contents($imgPath));
    } else {
        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['image']));
        $img = imagecreatefromstring($imgData);
    }

    if (!$img) {
        echo json_encode(['error' => 'Invalid image']);
        exit;
    }

    $algorithm = $_POST['algorithm'] ?? 'sobel';
    $symbols = $_POST['symbols'] ?? '@%#*+=-:. ';
    $colors = (int) ($_POST['colors'] ?? 8);
    $threshold = (int) ($_POST['threshold'] ?? 128);

    switch ($algorithm) {
        case 'sobel':
            $ascii = SobelEdgeService::convert($img, $symbols);
            break;
        case 'color_reduce':
            $ascii = ColorReduceService::convert($img, $symbols, $colors);
            break;
        case 'symbol_reduce':
            $ascii = SymbolReduceService::convert($img, $symbols);
            break;
        case 'threshold':
            $ascii = ThresholdService::convert($img, $symbols, $threshold);
            break;
        default:
            $ascii = SobelEdgeService::convert($img, $symbols);
    }
    imagedestroy($img);
    echo json_encode(['ascii' => $ascii]);
    exit;
}
echo json_encode(['error' => 'Invalid request']);