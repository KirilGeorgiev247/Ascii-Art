/**
 * Dynamically sets the font size of the ASCII output based on the longest line.
 * Also syncs the zoom slider and value display.
 */
function autoZoomAscii(pre, slider, valueDisplay) {
    const lines = pre.textContent.split('\n');
    const maxLen = lines.reduce((max, line) => Math.max(max, line.length), 0);

    // Container width in px (matches .ascii-output width)
    const containerWidth = pre.clientWidth || 800;

    // Each monospace char is about 0.6em wide, so:
    // fontSize = (containerWidth / maxLen) / 0.6
    let fontSize = (containerWidth / maxLen) / 0.6;

    // Clamp font size between 0.5 and 24
    fontSize = Math.max(0.5, Math.min(fontSize, 24));

    // Round to nearest 0.5 for slider sync
    fontSize = Math.round(fontSize * 2) / 2;

    pre.style.fontSize = fontSize + 'px';
    if (slider) slider.value = fontSize;
    if (valueDisplay) valueDisplay.textContent = fontSize + 'px';
}

/**
 * Sets up the zoom slider to control the ASCII output font size.
 */
function setupAsciiZoomSlider(preId = 'asciiOutput', sliderId = 'asciiZoom', valueId = 'asciiZoomValue') {
    const pre = document.getElementById(preId);
    const slider = document.getElementById(sliderId);
    const valueDisplay = document.getElementById(valueId);

    if (!pre || !slider || !valueDisplay) return;

    slider.addEventListener('input', function () {
        pre.style.fontSize = this.value + 'px';
        valueDisplay.textContent = this.value + 'px';
    });

    // Optionally, auto-zoom on load if there's content
    if (pre.textContent.trim()) {
        autoZoomAscii(pre, slider, valueDisplay);
    }
}

window.autoZoomAscii = autoZoomAscii;
window.setupAsciiZoomSlider = setupAsciiZoomSlider;