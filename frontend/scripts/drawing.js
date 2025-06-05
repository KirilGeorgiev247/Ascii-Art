const canvas = document.getElementById("drawingCanvas");
const ctx = canvas.getContext("2d");

let drawing = false;
let brushColor = document.getElementById("colorPicker").value;
let brushSize = document.getElementById("brushSize").value;

const ws = new WebSocket("ws://localhost:9501"); 

canvas.addEventListener("mousedown", () => drawing = true);
canvas.addEventListener("mouseup", () => drawing = false);
canvas.addEventListener("mouseout", () => drawing = false);
canvas.addEventListener("mousemove", handleDraw);

function handleDraw(e) {
  if (!drawing) return;

  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  const data = {
    type: "draw",
    x,
    y,
    color: brushColor,
    size: brushSize
  };

  drawDot(data);
  ws.send(JSON.stringify(data)); 
}

function drawDot({ x, y, color, size }) {
  ctx.fillStyle = color;
  ctx.beginPath();
  ctx.arc(x, y, size / 2, 0, Math.PI * 2);
  ctx.fill();
}

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  if (data.type === "draw") {
    drawDot(data);
  } else if (data.type === "clear") {
    clearCanvas();
  }
};

document.getElementById("colorPicker").addEventListener("input", e => {
  brushColor = e.target.value;
});

document.getElementById("brushSize").addEventListener("input", e => {
  brushSize = e.target.value;
});

function clearCanvas() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ws.send(JSON.stringify({ type: "clear" }));
}