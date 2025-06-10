let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

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

function addPost(post) {
  console.log("Tuka sme:", post);

  fetch("/add_posts", {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  })
    .then((response) => {
      return response.json();
    })
    .then((posts) => {
      console.log("Posts fetched:", posts);
      overwriteFeedWithPosts(posts);
    })
    .catch((error) => {
      console.error("Failed to fetch posts:", error);
    });
}

function overwriteFeedWithPosts(posts) {
  const container = document.getElementById("postsContainer");
  if (!container) return;
  container.innerHTML = "";

  if (!posts || posts.length === 0) {
    container.innerHTML = `
      <div class="loading">
        <i class="fas fa-palette" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p>No posts yet! Be the first to share some ASCII art.</p>
      </div>
    `;
    return;
  }

  posts.forEach((post) => {
    const postElement = createFeedPostElement(post);
    container.appendChild(postElement);
  });

  posts.forEach((post) => {
    if (window.setupAsciiZoomSlider) {
      window.setupAsciiZoomSlider(
        `asciiOutput-${post.id}`,
        `asciiZoom-${post.id}`,
        `asciiZoomValue-${post.id}`
      );
    }
  });
}

function createFeedPostElement(post) {
  const postId = post.id;
  const username = post.username || "Unknown User";
  const avatar = username.charAt(0).toUpperCase();
  const titleHtml = post.title
    ? `<h2 class="post-title">${escapeHtml(post.title)}</h2>`
    : "";

  const element = document.createElement("article");
  element.className = "post";
  element.setAttribute("data-post-id", postId);

  element.innerHTML = `
    <div class="post-header">
      <div class="user-info">
        <div class="user-avatar">${avatar}</div>
        <div class="user-details">
          <h4>${escapeHtml(username)}</h4>
          <div class="timestamp" data-timestamp="${post.created_at}">
            ${formatTimestamp(post.created_at)}
          </div>
        </div>
      </div>
      <div class="post-menu">
        <button class="interaction-btn" onclick="togglePostMenu(${postId})">
          <i class="fas fa-ellipsis-h"></i>
        </button>
      </div>
    </div>

    ${titleHtml}

    <div class="zoom-control" style="margin-bottom:0.5rem;">
      <label for="asciiZoom-${postId}" class="zoom-label">
        <i class="fas fa-search-plus"></i> Zoom:
      </label>
      <input type="range" id="asciiZoom-${postId}" min="0.5" max="24" value="12" step="0.5">
      <span id="asciiZoomValue-${postId}">12px</span>
    </div>

    <pre id="asciiOutput-${postId}" class="ascii-output">${escapeHtml(
    post.ascii_content || post.content || ""
  )}</pre>

    <div class="post-interactions">
      <div class="interaction-buttons">
        <button class="interaction-btn" onclick="likePost(${postId})">
          <i class="fas fa-heart"></i>
          <span id="likes-${postId}">${post.likes_count || 0}</span>
        </button>
        <button class="interaction-btn" onclick="sharePost(${postId})">
          <i class="fas fa-share"></i>
          Share
        </button>
      </div>
    </div>
  `;

  if (window.setupAsciiZoomSlider) {
    window.setupAsciiZoomSlider(
      `asciiOutput-${postId}`,
      `asciiZoom-${postId}`,
      `asciiZoomValue-${postId}`
    );
  }

  return element;
}

function escapeHtml(text) {
  if (!text) return "";
  return text.replace(/[&<>"']/g, function (m) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[m];
  });
}

function formatTimestamp(ts) {
  if (!ts) return "";
  const date = new Date(ts);
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function updateConnectionStatus(connected) {
  const status = document.getElementById("connectionStatus");
  if (connected) {
    status.className = "connection-status connected";
    xw;
    status.innerHTML = '<i class="fas fa-circle"></i> <span>Connected</span>';
  } else {
    status.className = "connection-status disconnected";
    status.innerHTML =
      '<i class="fas fa-circle"></i> <span>Disconnected</span>';
  }
}

function addNewPost(post) {}

function createPostElement(post) {
  const div = document.createElement("div");
  div.className = "post";
  div.dataset.postId = post.id;

  div.innerHTML = `
        <div class="post-header">
            <div class="user-info">
                <div class="user-avatar">${post.avatar}</div>
                <div class="user-details">
                    <h4>${post.username || "Unknown User"}</h4>
                    <div class="timestamp">${post.created_at}</div>
                </div>
            </div>
        </div>
        <div class="post-content">${post.content}</div>
        <div class="post-interactions">
            <div class="interaction-buttons">
                <button class="interaction-btn" onclick="likePost(${post.id})">
                    <i class="fas fa-heart"></i>
                    <span id="likes-${post.id}">0</span>
                </button>
            </div>
        </div>
    `;

  return div;
}

function createPost() {
  const postContentElement = document.getElementById("postContent");
  if (!postContentElement) {
    showDialog("Could not find post content area", "error");
    return;
  }

  const content = postContentElement.value.trim();

  if (!content) {
    showDialog("Please enter some content for your post!", "error");
    return;
  }

  try {
    if (ws && ws.readyState === WebSocket.OPEN) {
      sendMessage("create_post", {
        userId: userId,
        username: username,
        content: content,
      });
    }
  } catch (error) {
    console.error("Failed to send WebSocket message:", error);
  }

  fetch("/api/posts", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      title: "ASCII Art Post",
      content: content,
      type: "ascii_art",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("postContent").value = "";

        addNewPost({
          id: data.id,
          username: data.username,
          content: data.content,
          created_at: data.created_at,
        });
      }
    })
    .catch((error) => {
      console.error("HTTP request failed:", error);
    });
}

function likePost(postId) {
  fetch(`/feed/like/${postId}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updatePostLikes(postId, data.likes_count);
        toggleLikeButton(postId, data.action === "liked");
        sendMessage("like_post", { postId, likesCount: data.likes_count });
      } else {
        showDialog(data.error || "Failed to like post", "error");
      }
    })
    .catch((error) => {
      console.error("Failed to like post:", error);
      showDialog("Failed to like post", "error");
    });
}

function toggleLikeButton(postId, liked) {
  const btn = document.getElementById(`like-btn-${postId}`);
  if (btn) {
    if (liked) {
      btn.classList.add("liked");
    } else {
      btn.classList.remove("liked");
    }
  }
}

function updatePostLikes(postId, likes) {
  const likesElement = document.getElementById(`likes-${postId}`);
  if (likesElement) {
    likesElement.textContent = likes;
  }
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

function copyToCanvas(postId) {
  const post = document.querySelector(
    `[data-post-id="${postId}"] .post-content`
  );
  if (post) {
    localStorage.setItem("importedArt", post.textContent);
    window.location.href = "/draw";
  }
}

document.addEventListener("DOMContentLoaded", function () {
  connectWebSocket();

  document
    .getElementById("postContent")
    .addEventListener("keydown", function (e) {
      if (e.key === "Enter" && e.ctrlKey) {
        createPost();
      }
    });
});

document.addEventListener("DOMContentLoaded", function () {
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
