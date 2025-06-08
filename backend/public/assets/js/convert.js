document.getElementById("algorithmSelect").addEventListener("change", function () {
    document.getElementById("colorsInputGroup").style.display =
        this.value === "color_reduce" ? "" : "none";
    document.getElementById("thresholdInputGroup").style.display =
        this.value === "threshold" ? "" : "none";
});

document.getElementById("convertForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch("/api/convert", {
        method: "POST",
        body: formData,
    })
        .then((res) => res.json())
        .then((res) => {
            if (res.ascii) {
                const pre = document.getElementById("asciiOutput");
                const slider = document.getElementById("asciiZoom");
                const valueDisplay = document.getElementById("asciiZoomValue");
                pre.textContent = res.ascii;
                // Auto-zoom and sync slider/value
                if (window.autoZoomAscii) {
                    window.autoZoomAscii(pre, slider, valueDisplay);
                }
            } else {
                showDialog(res.error || "Conversion failed", "error");
            }
        });
});

// Setup zoom slider on page load
document.addEventListener('DOMContentLoaded', function () {
    if (window.setupAsciiZoomSlider) {
        window.setupAsciiZoomSlider();
    }
});

function saveAsciiArt() {
    const asciiContent = document.getElementById('asciiOutput').textContent;
    if (!asciiContent.trim()) {
        showDialog("Nothing to save!", "error");
        return;
    }
    showDialog(
        "Enter a title for your ASCII art:",
        "question",
        {
            input: true,
            inputType: "text",
            inputPlaceholder: "ASCII art title",
            okText: "Share",
            cancelText: "Cancel",
            showCancel: true,
            onOk: function(title) {
                if (!title || !title.trim()) {
                    showDialog("Title is required.", "error");
                    return;
                }
                fetch('/image/save', {
                    method: 'POST',
                    body: new URLSearchParams({
                        title: title,
                        ascii_content: asciiContent
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        showDialog('ASCII art saved and shared!', "success");
                    }
                });
            },
            onCancel: function () {
                // Do nothing, user cancelled
            }
        }
    );
}