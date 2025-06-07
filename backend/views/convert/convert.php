<?php
$hideHeader = false;
$minimalLayout = false;
$title = "Convert Image to ASCII Art";
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/convert.css" rel="stylesheet" />
    <link href="/assets/css/zoom.css" rel="stylesheet" />
</head>

<body>
    <main class="convert-main">
        <header class="convert-header">
            <h1><i class="fas fa-image"></i> Convert Image to ASCII Art</h1>
            <p>Upload an image and choose an algorithm to convert it to ASCII art!</p>
        </header>
        <section class="convert-tools">
            <form id="convertForm" enctype="multipart/form-data">
                <input type="file" name="image" id="imageInput" accept="image/*" required />
                <select name="algorithm" id="algorithmSelect">
                    <option value="sobel">Edge Detection (Sobel)</option>
                    <option value="color_reduce">Reduced Colors</option>
                    <option value="symbol_reduce">Reduced Symbols</option>
                    <option value="threshold">Threshold</option>
                </select>
                <input type="text" name="symbols" id="symbolsInput" placeholder="Symbols (e.g. @%#*+=-:. )" />
                <input type="number" name="colors" id="colorsInput" placeholder="Colors (for color reduce)" min="2"
                    max="32" style="display:none" />
                <input type="number" name="threshold" id="thresholdInput" placeholder="Threshold (for threshold)"
                    min="0" max="255" style="display:none" />
                <button type="submit" class="convert-btn"><i class="fas fa-magic"></i> Convert</button>
            </form>
        </section>
        <section>
            <div class="zoom-control">
                <label for="asciiZoom" class="zoom-label"><i class="fas fa-search-plus"></i> Zoom:</label>
                <input type="range" id="asciiZoom" min="0.5" max="24" value="12" step="0.5">
                <span id="asciiZoomValue">12px</span>
            </div>
            <pre id="asciiOutput" class="ascii-output"></pre>
            <button class="convert-btn" onclick="saveAsciiArt()"><i class="fas fa-share"></i> Share as Post</button>
        </section>
    </main>

    <script src="/assets/js/convert.js"></script>
    <script src="/assets/js/zoom.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>