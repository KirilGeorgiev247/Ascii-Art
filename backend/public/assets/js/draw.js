let tool = 'pencil';
let color = '#000000';
const canvas = document.getElementById('drawCanvas');
const ctx = canvas.getContext('2d');
const pencilWidthInput = document.getElementById('pencilWidth');
const pencilWidthValue = document.getElementById('pencilWidthValue');
const width = canvas.width;
const height = canvas.height;
let drawing = false;
let pencilWidth = 3;

let lastX = null, lastY = null;

ctx.lineCap = "round";
ctx.lineJoin = "round";

ctx.fillStyle = '#ffffff';
ctx.fillRect(0, 0, width, height);

if (pencilWidthInput && pencilWidthValue) {
    pencilWidthInput.addEventListener('input', function() {
        pencilWidth = parseInt(this.value, 10);
        pencilWidthValue.textContent = this.value;
    });
}

function setTool(t) {
    tool = t;
    document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('colorPicker').classList.remove('active');

    let selector = '';
    if (t === 'pencil') selector = '.tool-btn[title="Pencil"]';
    else if (t === 'bucket') selector = '.tool-btn[title="Flood Fill"]';
    else if (t === 'color') selector = '.tool-btn[title="Change Color"]';
    if (selector) {
        const btn = document.querySelector(selector);
        if (btn) btn.classList.add('active');
    }

    if (t === 'color') {
        document.getElementById('colorPicker').classList.add('active');
    }
}
function capitalize(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

document.getElementById('colorPicker').addEventListener('input', e => color = e.target.value);

canvas.addEventListener('mousedown', e => {
    drawing = true;
    [lastX, lastY] = getCanvasCoords(e);
    handleDraw(e);
});
canvas.addEventListener('mouseup', () => {
    drawing = false;
    lastX = null; lastY = null;
});
canvas.addEventListener('mouseleave', () => {
    drawing = false;
    lastX = null; lastY = null;
});
canvas.addEventListener('mousemove', e => {
    if (drawing && tool === 'pencil') handleDraw(e);
});
canvas.addEventListener('click', e => {
    if (tool === 'bucket') handleFloodFill(e);
    if (tool === 'color') handleChangeColor(e);
});

function handleDraw(e) {
    if (tool !== 'pencil') return;
    const [x, y] = getCanvasCoords(e);

    ctx.strokeStyle = color;
    ctx.lineWidth = pencilWidth;

    if (lastX !== null && lastY !== null) {
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
    } else {
        ctx.beginPath();
        ctx.arc(x, y, pencilWidth / 2, 0, Math.PI * 2, true);
        ctx.fillStyle = color;
        ctx.fill();
    }

    lastX = x;
    lastY = y;
}

function handleFloodFill(e) {
    const [x, y] = getCanvasCoords(e);
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const targetColor = getColorAt(data, x, y);
    const replacementColor = hexToRgb(color);
    if (colorsEqual(targetColor, replacementColor)) return;
    // Use API for flood fill
    fetch('/api/draw', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'flood_fill',
            canvas: getCanvasArray(data, width, height),
            x, y,
            targetColor,
            replacementColor
        })
    })
    .then(res => res.json())
    .then(res => {
        setCanvasFromArray(res.canvas);
    });
}

function handleChangeColor(e) {
    const [x, y] = getCanvasCoords(e);
    const imageData = ctx.getImageData(0, 0, width, height);
    const data = imageData.data;
    const fromColor = getColorAt(data, x, y);
    const toColor = hexToRgb(color);
    fetch('/api/draw', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'change_color',
            canvas: getCanvasArray(data, width, height),
            fromColor,
            toColor
        })
    })
    .then(res => res.json())
    .then(res => {
        setCanvasFromArray(res.canvas);
    });
}

function getCanvasCoords(e) {
    const rect = canvas.getBoundingClientRect();
    const x = Math.floor((e.clientX - rect.left) * (width / rect.width));
    const y = Math.floor((e.clientY - rect.top) * (height / rect.height));
    return [x, y];
}

function getColorAt(data, x, y) {
    const idx = (y * width + x) * 4;
    return [data[idx], data[idx+1], data[idx+2], data[idx+3]];
}
function colorsEqual(a, b) {
    return a[0] === b[0] && a[1] === b[1] && a[2] === b[2] && a[3] === b[3];
}
function hexToRgb(hex) {
    hex = hex.replace('#','');
    return [
        parseInt(hex.substring(0,2),16),
        parseInt(hex.substring(2,4),16),
        parseInt(hex.substring(4,6),16),
        255
    ];
}
function getCanvasArray(data, w, h) {
    const arr = [];
    for (let y = 0; y < h; y++) {
        const row = [];
        for (let x = 0; x < w; x++) {
            const idx = (y * w + x) * 4;
            row.push([data[idx], data[idx+1], data[idx+2], data[idx+3]]);
        }
        arr.push(row);
    }
    return arr;
}
function setCanvasFromArray(arr) {
    const imageData = ctx.createImageData(width, height);
    for (let y = 0; y < arr.length; y++) {
        for (let x = 0; x < arr[0].length; x++) {
            const idx = (y * width + x) * 4;
            const [r,g,b,a] = arr[y][x];
            imageData.data[idx] = r;
            imageData.data[idx+1] = g;
            imageData.data[idx+2] = b;
            imageData.data[idx+3] = a;
        }
    }
    ctx.putImageData(imageData, 0, 0);
}

// Import/Export
function importImage() {
    document.getElementById('importInput').click();
}
document.getElementById('importInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const img = new Image();
    img.onload = function() {
        ctx.drawImage(img, 0, 0, width, height);
    };
    img.src = URL.createObjectURL(file);
});

function exportAscii() {
    const imageData = ctx.getImageData(0, 0, width, height);
    const ascii = imageToAscii(imageData);
    document.getElementById('asciiOutput').value = ascii;
}

function imageToAscii(imageData) {
    const chars = "@%#*+=-:. ";
    // Target ASCII output size
    const asciiWidth = 80;
    const asciiHeight = 40;
    const cellWidth = Math.floor(imageData.width / asciiWidth);
    const cellHeight = Math.floor(imageData.height / asciiHeight);
    let ascii = "";
    for (let y = 0; y < asciiHeight; y++) {
        for (let x = 0; x < asciiWidth; x++) {
            // Average color in the cell
            let r = 0, g = 0, b = 0, count = 0;
            for (let dy = 0; dy < cellHeight; dy++) {
                for (let dx = 0; dx < cellWidth; dx++) {
                    const px = x * cellWidth + dx;
                    const py = y * cellHeight + dy;
                    if (px < imageData.width && py < imageData.height) {
                        const idx = (py * imageData.width + px) * 4;
                        r += imageData.data[idx];
                        g += imageData.data[idx+1];
                        b += imageData.data[idx+2];
                        count++;
                    }
                }
            }
            if (count === 0) count = 1;
            const avg = (r + g + b) / (3 * count);
            const charIdx = Math.floor((avg / 255) * (chars.length - 1));
            ascii += chars[charIdx];
        }
        ascii += "\n";
    }
    return ascii;
}

function saveDrawing() {
    const asciiContent = document.getElementById('asciiOutput').value;
    showDialog(
        "Enter a title for your drawing:",
        "question",
        {
            input: true,
            inputType: "text",
            inputPlaceholder: "Drawing title",
            okText: "Save",
            cancelText: "Cancel",
            showCancel: true,
            onOk: function(title) {
                if (!title || !title.trim()) {
                    showDialog("Title is required.", "error");
                    return;
                }
                fetch('/draw/save', {
                    method: 'POST',
                    body: new URLSearchParams({
                        title: title,
                        ascii_content: asciiContent
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showDialog('Drawing saved and shared!', "success");
                    }
                });
            },
            onCancel: function () {
                // Do nothing, user cancelled
            }
        }
    );
}

function clearCanvas() {
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    // If you have an ASCII output textarea, clear it too:
    const asciiOutput = document.getElementById('asciiOutput');
    if (asciiOutput) asciiOutput.value = '';
}

function downloadAscii() {
    const asciiOutput = document.getElementById('asciiOutput');
    const asciiContent = asciiOutput.value !== undefined ? asciiOutput.value : asciiOutput.textContent;
    if (!asciiContent || !asciiContent.trim()) {
        showDialog("Nothing to save!", "error");
        return;
    }
    showDialog(
        "Enter a file name for your ASCII art:",
        "question",
        {
            input: true,
            inputType: "text",
            inputPlaceholder: "ascii-art.txt",
            okText: "Download",
            cancelText: "Cancel",
            showCancel: true,
            onOk: function(filename) {
                filename = (filename && filename.trim()) ? filename.trim() : "ascii-art.txt";
                if (!filename.endsWith('.txt')) filename += '.txt';
                const blob = new Blob([asciiContent], { type: "text/plain" });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                setTimeout(() => {
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                }, 100);
            }
        }
    );
}

// TODO: check if should delete
// Optional: Load ASCII art from localStorage (for editing)
window.addEventListener('DOMContentLoaded', () => {
    const importedArt = localStorage.getItem('importedArt');
    if (importedArt) {
        document.getElementById('asciiOutput').value = importedArt;
        localStorage.removeItem('importedArt');
    }
});