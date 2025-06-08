let tool = 'pencil';
let color = '#000000';
const canvas = document.getElementById('drawCanvas');
const ctx = canvas.getContext('2d');
const width = canvas.width;
const height = canvas.height;
let drawing = false;

// Initialize blank canvas
ctx.fillStyle = '#ffffff';
ctx.fillRect(0, 0, width, height);

function setTool(t) {
    tool = t;
    document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.tool-btn[title="${capitalize(t)}"]`).classList.add('active');
}
function capitalize(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

document.getElementById('colorPicker').addEventListener('input', e => color = e.target.value);

canvas.addEventListener('mousedown', e => {
    drawing = true;
    handleDraw(e);
});
canvas.addEventListener('mouseup', () => drawing = false);
canvas.addEventListener('mouseleave', () => drawing = false);
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
    ctx.fillStyle = color;
    ctx.fillRect(x, y, 1, 1);
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

// Optional: Load ASCII art from localStorage (for editing)
window.addEventListener('DOMContentLoaded', () => {
    const importedArt = localStorage.getItem('importedArt');
    if (importedArt) {
        document.getElementById('asciiOutput').value = importedArt;
        localStorage.removeItem('importedArt');
    }
});