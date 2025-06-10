function autoZoomAscii(pre, slider, valueDisplay) {
  const lines = pre.textContent.split("\n");
  const maxLen = lines.reduce((max, line) => Math.max(max, line.length), 0);

  const containerWidth = pre.clientWidth || 800;

  let fontSize = containerWidth / maxLen / 0.6;

  fontSize = Math.max(0.5, Math.min(fontSize, 24));

  fontSize = Math.round(fontSize * 2) / 2;

  pre.style.fontSize = fontSize + "px";
  if (slider) slider.value = fontSize;
  if (valueDisplay) valueDisplay.textContent = fontSize + "px";
}

function setupAsciiZoomSlider(
  preId = "asciiOutput",
  sliderId = "asciiZoom",
  valueId = "asciiZoomValue"
) {
  const pre = document.getElementById(preId);
  const slider = document.getElementById(sliderId);
  const valueDisplay = document.getElementById(valueId);

  if (!pre || !slider || !valueDisplay) return;

  slider.addEventListener("input", function () {
    pre.style.fontSize = this.value + "px";
    valueDisplay.textContent = this.value + "px";
  });

  if (pre.textContent.trim()) {
    autoZoomAscii(pre, slider, valueDisplay);
  }
}

window.autoZoomAscii = autoZoomAscii;
window.setupAsciiZoomSlider = setupAsciiZoomSlider;
