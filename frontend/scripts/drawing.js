const canvas = document.getElementById("drawingCanvas");
const ctx = canvas.getContext("2d");

let drawing = false;
let brushColor = document.getElementById("colorPicker").value;
let brushSize = document.getElementById("brushSize").value;

canvas.addEventListener("mousedown", () => drawing = true);
canvas.addEventListener("mouseup", () => drawing = false);
canvas.addEventListener("mouseout", () => drawing = false);
canvas.addEventListener("mousemove", draw);

function draw(e) {
  if (!drawing) return;
  const rect = canvas.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  ctx.fillStyle = brushColor;
  ctx.beginPath();
  ctx.arc(x, y, brushSize / 2, 0, Math.PI * 2);
  ctx.fill();
}

document.getElementById("colorPicker").addEventListener("input", e => {
  brushColor = e.target.value;
});

document.getElementById("brushSize").addEventListener("input", e => {
  brushSize = e.target.value;
});

function clearCanvas() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}