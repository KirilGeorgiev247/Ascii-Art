const descriptions = {
  sobel:
    "Sobel Edge Detection: Applies the Sobel operator to emphasize the edges and outlines in your image, converting the result into high-contrast ASCII art. Great for emphasizing structure and contours.",
  color_reduce:
    "Reduced Colors (Color Palette Reduction): Simplifies your image by reducing the number of distinct colors before converting it to ASCII. You can control the number of colors used for a stylized effect.",
  symbol_reduce:
    "Reduced Symbols (Symbol Set Reduction): Limits the ASCII output to a custom set of symbols you choose. Useful for artistic control over how detailed or abstract the final image looks.",
  threshold:
    "Threshold (Binary Thresholding): Turns your image into a two-tone (light and dark) ASCII rendering by applying a brightness threshold. Pixels brighter than the threshold use one symbol, darker ones use another.",
};

document
  .getElementById("algorithmSelect")
  .addEventListener("change", function () {
    document.getElementById("colorsInputGroup").style.display =
      this.value === "color_reduce" ? "" : "none";
    document.getElementById("thresholdInputGroup").style.display =
      this.value === "threshold" ? "" : "none";
    document.getElementById("algorithmDescriptionText").textContent =
      descriptions[this.value] || "";
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
        if (window.autoZoomAscii) {
          window.autoZoomAscii(pre, slider, valueDisplay);
        }
      } else {
        showDialog(res.error || "Conversion failed", "error");
      }
    });
});

document.addEventListener("DOMContentLoaded", function () {
  if (window.setupAsciiZoomSlider) {
    window.setupAsciiZoomSlider();
  }
});

function saveAsciiArt() {
  const asciiContent = document.getElementById("asciiOutput").textContent;
  if (!asciiContent.trim()) {
    showDialog("Nothing to save!", "error");
    return;
  }
  showDialog("Enter a title for your ASCII art:", "question", {
    input: true,
    inputType: "text",
    inputPlaceholder: "ASCII art title",
    okText: "Share",
    cancelText: "Cancel",
    showCancel: true,
    onOk: function (title) {
      if (!title || !title.trim()) {
        showDialog("Title is required.", "error");
        return;
      }
      fetch("/image/save", {
        method: "POST",
        body: new URLSearchParams({
          title: title,
          ascii_content: asciiContent,
        }),
      })
        .then((res) => res.json())
        .then((res) => {
          if (res.success) {
            sendMessage("add_post");
            showDialog("ASCII art saved and shared!", "success");
          }
        });
    },
    onCancel: function () {},
  });
}

function connectWebSocket() {
  try {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = function () {
      console.log("Open Websocket from draw");
    };

    ws.onmessage = function (event) {
      console.log("Message Websocket from draw");
    };

    ws.onclose = function (event) {
      console.log("Close Websocket from draw");

      if (reconnectAttempts < maxReconnectAttempts) {
        setTimeout(() => {
          reconnectAttempts++;
          connectWebSocket();
        }, 2000 * reconnectAttempts);
      }
    };

    ws.onerror = function (error) {
      console.error("WebSocket error:", error);
    };
  } catch (error) {
    console.error("Error connecting to WebSocket:", error);
  }
}

function sendMessage(type, payload) {
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(
      JSON.stringify({
        type: type,
        payload: payload,
      })
    );
  } else {
    console.warn(
      "WebSocket not available or not open. State:",
      ws ? ws.readyState : "null"
    );
  }
}

window.addEventListener("DOMContentLoaded", () => {
  connectWebSocket();
});
