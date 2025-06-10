let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;
const userId = window.profileUserId;
const profileUserId = window.profileProfileUserId;
const isOwnProfile = window.profileIsOwnProfile;

function connectWebSocket() {
  try {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = function () {
      console.log("WebSocket connected");
      updateConnectionStatus(true);
      reconnectAttempts = 0;
    };

    ws.onmessage = function (event) {
      try {
        const data = JSON.parse(event.data);

        console.log(data);

        handleWebSocketMessage(data);
      } catch (e) {
        console.error("Error parsing WebSocket message:", e);
      }
    };

    ws.onclose = function (event) {
      const data = JSON.parse(event.data);
      console.log("WebSocket disconnected:", data);

      console.log("WebSocket disconnected");
      updateConnectionStatus(false);

      if (reconnectAttempts < maxReconnectAttempts) {
        setTimeout(() => {
          reconnectAttempts++;
          connectWebSocket();
        }, 2000 * reconnectAttempts);
      }
    };

    ws.onerror = function (error) {
      console.error("WebSocket error:", error);
      updateConnectionStatus(false);
    };
  } catch (error) {
    console.error("Error connecting to WebSocket:", error);
    updateConnectionStatus(false);
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

function handleWebSocketMessage(data) {
  switch (data.type) {
    case "add_post":
      addPost(data.payload);
      break;
    case "like_post":
      updatePostLikes(data.payload.postId, data.payload.likesCount);
      break;
    default:
      console.log("Unknown message type:", data.type);
  }
}

function updateConnectionStatus(connected) {
  const status = document.getElementById("connectionStatus");
  if (connected) {
    status.className = "connection-status connected";
    status.innerHTML = '<i class="fas fa-circle"></i> <span>Connected</span>';
  } else {
    status.className = "connection-status disconnected";
    status.innerHTML =
      '<i class="fas fa-circle"></i> <span>Disconnected</span>';
  }
}

function likePost(postId) {
  console.log("Like post:", postId);
}

function sharePost(postId) {
  const post = document.querySelector(
    `[data-post-id="${postId}"] .post-content`
  );
  if (post) {
    navigator.clipboard.writeText(post.textContent).then(() => {
      showDialog("ASCII art copied to clipboard!", "info");
    });
  }
}

function deletePost(postId) {
  showDialog("Are you sure you want to delete this post?", "question", {
    okText: "Delete",
    cancelText: "Cancel",
    showCancel: true,
    onOk: function () {
      fetch(`/profile/post/${postId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => response.json())
        .then((response) => {
          console.log(response);
          return response;
        })
        .then((data) => {
          if (data.success) {
            const postElem = document.querySelector(
              `[data-post-id="${postId}"]`
            );
            if (postElem) postElem.remove();
            sendMessage("add_post");
            showDialog("Post deleted successfully.", "success", {
              onOk: function () {
                location.reload();
              },
            });
          } else {
            showDialog(data.error || "Failed to delete post.", "error");
          }
        })
        .catch(() => {
          showDialog("Server error. Could not delete post.", "error");
        });
    },
  });
}

document.addEventListener("DOMContentLoaded", function () {
  connectWebSocket();

  document.querySelectorAll(".post").forEach(function (postElem) {
    const postId = postElem.getAttribute("data-post-id");
    if (!postId) return;
    if (window.setupAsciiZoomSlider) {
      window.setupAsciiZoomSlider(
        "asciiOutput-" + postId,
        "asciiZoom-" + postId,
        "asciiZoomValue-" + postId
      );
    }
  });
});
