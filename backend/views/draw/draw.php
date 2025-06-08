<?php
$hideHeader = false;
$minimalLayout = false;
$title = "Draw - ASCII Art Social Network";
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="/assets/css/draw.css" rel="stylesheet" />
</head>

<body>
    <main class="draw-main">
        <header class="draw-header">
            <h1><i class="fas fa-paint-brush"></i> Draw & Share ASCII Art</h1>
            <p>Use the tools below to create, import, export, and share your art!</p>
        </header>
        <section class="draw-tools">
            <button onclick="setTool('pencil')" class="tool-btn active" title="Pencil"><i
                    class="fas fa-pencil-alt"></i></button>
            <button onclick="setTool('bucket')" class="tool-btn" title="Flood Fill"><i
                    class="fas fa-fill-drip"></i></button>
            <button onclick="setTool('color')" class="tool-btn" title="Change Color"><i
                    class="fas fa-tint"></i></button>
            <button onclick="clearCanvas()" class="tool-btn" title="Clear Canvas"><i class="fas fa-eraser"></i></button>
            <input type="color" id="colorPicker" value="#000000" title="Pick Color" />
            <button onclick="importImage()" class="tool-btn" title="Import"><i class="fas fa-file-import"></i></button>
            <button onclick="exportAscii()" class="tool-btn" title="Export ASCII"><i
                    class="fas fa-file-export"></i></button>
            <button onclick="downloadAscii()" class="tool-btn" title="Dowmload"><i class="fas fa-download"></i></button>
            <button onclick="saveDrawing()" class="tool-btn" title="Share"><i class="fas fa-share"></i></button>
            <input type="file" id="importInput" accept="image/*" style="display:none" />
        </section>
        <section class="draw-canvas-section">
            <canvas id="drawCanvas" width="640" height="320" tabindex="0"></canvas>
        </section>
        <section>
            <textarea id="asciiOutput" readonly rows="40" cols="80" style="width:100%;margin-top:1rem;"></textarea>
        </section>
    </main>
    <script src="/assets/js/draw.js"></script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layout/layout.php';
?>