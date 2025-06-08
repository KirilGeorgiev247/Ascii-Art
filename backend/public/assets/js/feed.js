let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

// WebSocket connection
function connectWebSocket() {
  try {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = function () {
      console.log("WebSocket connected");
      updateConnectionStatus(true);
      reconnectAttempts = 0;

      // Send join message
      sendMessage("join_feed", {
        userId: userId,
        username: username,
      });
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

      sendMessage("left_feed", {
        userId: userId,
        username: username,
      });

      console.log("WebSocket disconnected");
      updateConnectionStatus(false);

      // Attempt to reconnect
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
    case "join_feed":
      // updateOnlineUsers('positive');
      console.log("Joined feed:", data.payload);
    case "left_feed":
      // updateOnlineUsers('negative');
      console.log("Left feed:", data.payload);
    case "test_post":
      // addNewPost(data.payload);
      testAddPost(data.payload);
      break;
    case "post_liked":
      updatePostLikes(data.payload.postId, data.payload.likes);
      break;
    case "users_online":
      updateOnlineUsers(data.payload.users);
      break;
    case "user_joined":
      addOnlineUser(data.payload);
      break;
    case "user_left":
      removeOnlineUser(data.payload.userId);
      break;
    default:
      console.log("Unknown message type:", data.type);
  }
}

function testAddPost(post) {
  console.log("Tuka sme:", post);

  // Fetch all posts for the feed (GET /api/posts?feed=1)
  fetch("/test_posts", {
    method: "GET",
  })
    .then((response) => {
      console.log("Ayde responsa:", JSON.stringify(response));
      console.log("Ayde responsa:" + response.status);
      console.log("Ayde responsa:" + response.json);
    })
    .then((posts) => {
      overwriteFeedWithPosts(posts);
    })
    .catch((error) => {
      console.error("Failed to fetch posts:", error);
    });
}

// Helper to overwrite the feed with new posts
function overwriteFeedWithPosts(posts) {
  const container = document.getElementById("postsContainer");
  if (!container) return;
  container.innerHTML = ""; // Clear current posts

  posts.forEach((post) => {
    const postElement = createFeedPostElement(post);
    container.appendChild(postElement);
  });
}

// Helper to create a post element matching feed.php HTML
function createFeedPostElement(post) {
  const article = document.createElement("article");
  article.className = "post";
  article.setAttribute("data-post-id", post.id);

  // User avatar (first letter)
  const avatar = document.createElement("div");
  avatar.className = "user-avatar";
  avatar.textContent = post.username
    ? post.username.charAt(0).toUpperCase()
    : "U";

  // Username and timestamp
  const userDetails = document.createElement("div");
  userDetails.className = "user-details";
  userDetails.innerHTML = `
        <h4>${escapeHtml(post.username || "Unknown User")}</h4>
        <div class="timestamp" data-timestamp="${post.created_at}">
            ${formatTimestamp(post.created_at)}
        </div>
    `;

  // User info
  const userInfo = document.createElement("div");
  userInfo.className = "user-info";
  userInfo.appendChild(avatar);
  userInfo.appendChild(userDetails);

  // Post header
  const postHeader = document.createElement("div");
  postHeader.className = "post-header";
  postHeader.appendChild(userInfo);

  // Post title
  let postTitleHtml = "";
  if (post.title) {
    postTitleHtml = `<h2 class="post-title">${escapeHtml(post.title)}</h2>`;
  }

  // Zoom control (optional, can be omitted or implemented as needed)
  let zoomHtml = `
        <div class="zoom-control" style="margin-bottom:0.5rem;">
            <label class="zoom-label">
                <i class="fas fa-search-plus"></i> Zoom:
            </label>
            <input type="range" min="0.5" max="24" value="12" step="0.5">
            <span>12px</span>
        </div>
    `;

  // ASCII art or content
  let asciiHtml = `<pre class="ascii-output">${escapeHtml(
    post.ascii_content || post.content || ""
  )}</pre>`;

  // Post interactions (likes)
  let interactionsHtml = `
        <div class="post-interactions">
            <div class="interaction-buttons">
                <button class="interaction-btn" onclick="likePost(${post.id})">
                    <i class="fas fa-heart"></i>
                    <span id="likes-${post.id}">${post.likes_count || 0}</span>
                </button>
            </div>
        </div>
    `;

  article.innerHTML = `
        ${postHeader.outerHTML}
        ${postTitleHtml}
        ${zoomHtml}
        ${asciiHtml}
        ${interactionsHtml}
    `;

  return article;
}

// Utility to escape HTML
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

// Utility to format timestamp (matches PHP date format in feed.php)
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
    status.innerHTML = '<i class="fas fa-circle"></i> <span>Connected</span>';
  } else {
    status.className = "connection-status disconnected";
    status.innerHTML =
      '<i class="fas fa-circle"></i> <span>Disconnected</span>';
  }
}

function addNewPost(post) {
  // console.log('Tuka sme:', post);
  // const container = document.getElementById('postsContainer');
  // const postElement = createPostElement(post);
  // postElement.classList.add('new-post');
  // // Insert at the beginning
  // container.insertBefore(postElement, container.firstChild);
  // // Remove the new-post class after animation
  // setTimeout(() => {
  //     postElement.classList.remove('new-post');
  // }, 300);
}

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

function updateOnlineUsers(users) {
  const usersList = document.getElementById("usersList");
  usersList.innerHTML = "";

  users.forEach((user) => {
    const userDiv = document.createElement("div");
    userDiv.className = "online-user";
    userDiv.innerHTML = `
            <div class="online-indicator"></div>
            <span>${user.username}</span>
        `;
    usersList.appendChild(userDiv);
  });
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

  // Send via WebSocket for real-time updates
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

  // Also send to server via HTTP
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

        // Add the new post to the feed immediately
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
  sendMessage("like_post", {
    postId: postId,
    userId: userId,
  });
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
