let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

// WebSocket connection
function connectWebSocket() {
    try {
        ws = new WebSocket('ws://localhost:8080');
        
        ws.onopen = function() {
            console.log('WebSocket connected');
            updateConnectionStatus(true);
            reconnectAttempts = 0;
            
            // Send join message
            sendMessage('join_feed', {
                userId: userId,
                username: username
            });
        };
        
        ws.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                handleWebSocketMessage(data);
            } catch (e) {
                console.error('Error parsing WebSocket message:', e);
            }
        };
        
        ws.onclose = function() {
            console.log('WebSocket disconnected');
            updateConnectionStatus(false);
            
            // Attempt to reconnect
            if (reconnectAttempts < maxReconnectAttempts) {
                setTimeout(() => {
                    reconnectAttempts++;
                    connectWebSocket();
                }, 2000 * reconnectAttempts);
            }
        };
        
        ws.onerror = function(error) {
            console.error('WebSocket error:', error);
            updateConnectionStatus(false);
        };
        
    } catch (error) {
        console.error('Error connecting to WebSocket:', error);
        updateConnectionStatus(false);
    }
}

function sendMessage(type, payload) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({
            type: type,
            payload: payload
        }));
    } else {
        console.warn('WebSocket not available or not open. State:', ws ? ws.readyState : 'null');
    }
}

function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'new_post':
            addNewPost(data.payload);
            break;
        case 'post_liked':
            updatePostLikes(data.payload.postId, data.payload.likes);
            break;
        case 'users_online':
            updateOnlineUsers(data.payload.users);
            break;
        case 'user_joined':
            addOnlineUser(data.payload);
            break;
        case 'user_left':
            removeOnlineUser(data.payload.userId);
            break;
        default:
            console.log('Unknown message type:', data.type);
    }
}

function updateConnectionStatus(connected) {
    const status = document.getElementById('connectionStatus');
    if (connected) {
        status.className = 'connection-status connected';
        status.innerHTML = '<i class="fas fa-circle"></i> <span>Connected</span>';
    } else {
        status.className = 'connection-status disconnected';
        status.innerHTML = '<i class="fas fa-circle"></i> <span>Disconnected</span>';
    }
}

function addNewPost(post) {
    const container = document.getElementById('postsContainer');
    const postElement = createPostElement(post);
    postElement.classList.add('new-post');
    
    // Insert at the beginning
    container.insertBefore(postElement, container.firstChild);
    
    // Remove the new-post class after animation
    setTimeout(() => {
        postElement.classList.remove('new-post');
    }, 300);
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post';
    div.dataset.postId = post.id;
    
    const avatar = post.username ? post.username.charAt(0).toUpperCase() : 'U';
    const timestamp = new Date(post.created_at).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
    
    div.innerHTML = `
        <div class="post-header">
            <div class="user-info">
                <div class="user-avatar">${avatar}</div>
                <div class="user-details">
                    <h4>${post.username || 'Unknown User'}</h4>
                    <div class="timestamp">${timestamp}</div>
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
                <button class="interaction-btn" onclick="sharePost(${post.id})">
                    <i class="fas fa-share"></i>
                    Share
                </button>
                <button class="interaction-btn" onclick="copyToCanvas(${post.id})">
                    <i class="fas fa-paint-brush"></i>
                    Edit
                </button>
            </div>
        </div>
    `;
    
    return div;
}

function updateOnlineUsers(users) {
    const usersList = document.getElementById('usersList');
    usersList.innerHTML = '';
    
    users.forEach(user => {
        const userDiv = document.createElement('div');
        userDiv.className = 'online-user';
        userDiv.innerHTML = `
            <div class="online-indicator"></div>
            <span>${user.username}</span>
        `;
        usersList.appendChild(userDiv);
    });
}

function createPost() {
    const postContentElement = document.getElementById('postContent');
    if (!postContentElement) {
        showDialog('Could not find post content area', 'error');
        return;
    }
    
    const content = postContentElement.value.trim();
    
    if (!content) {
        showDialog('Please enter some content for your post!', 'error');
        return;
    }
    
    // Send via WebSocket for real-time updates
    try {
        if (ws && ws.readyState === WebSocket.OPEN) {
            sendMessage('create_post', {
                userId: userId,
                username: username,
                content: content
            });
        }
    } catch (error) {
        console.error('Failed to send WebSocket message:', error);
    }
    
    // Also send to server via HTTP
    fetch('/api/posts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            title: 'ASCII Art Post',
            content: content,
            type: 'ascii_art'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('postContent').value = '';
            
            // Add the new post to the feed immediately
            addNewPost({
                id: data.id,
                username: data.username,
                content: data.content,
                created_at: data.created_at
            });
        }
    })
    .catch(error => {
        console.error('HTTP request failed:', error);
    });
}

function likePost(postId) {
    sendMessage('like_post', {
        postId: postId,
        userId: userId
    });
}

function updatePostLikes(postId, likes) {
    const likesElement = document.getElementById(`likes-${postId}`);
    if (likesElement) {
        likesElement.textContent = likes;
    }
}

function sharePost(postId) {
    const post = document.querySelector(`[data-post-id="${postId}"] .post-content`);
    if (post) {
        navigator.clipboard.writeText(post.textContent).then(() => {
            showDialog('ASCII art copied to clipboard!', "info");
        });
    }
}

function copyToCanvas(postId) {
    const post = document.querySelector(`[data-post-id="${postId}"] .post-content`);
    if (post) {
        localStorage.setItem('importedArt', post.textContent);
        window.location.href = '/draw';
    }
}

// ASCII Art Templates
function insertTemplate(type) {
    const textarea = document.getElementById('postContent');
    let template = '';
    
    switch (type) {
        case 'box':
            template = `╔══════════════════╗
║    Your Text     ║
╚══════════════════╝`;
            break;
        case 'heart':
            template = `    ♥♥        ♥♥
  ♥♥♥♥♥♥    ♥♥♥♥♥♥
♥♥♥♥♥♥♥♥  ♥♥♥♥♥♥♥♥
♥♥♥♥♥♥♥♥♥♥♥♥♥♥♥♥
  ♥♥♥♥♥♥♥♥♥♥♥♥
    ♥♥♥♥♥♥♥♥
      ♥♥♥♥
        ♥`;
            break;
        case 'star':
            template = `    ⭐
   ⭐⭐⭐
  ⭐⭐⭐⭐⭐
 ⭐⭐⭐⭐⭐⭐⭐
⭐⭐⭐⭐⭐⭐⭐⭐⭐
 ⭐⭐⭐⭐⭐⭐⭐
  ⭐⭐⭐⭐⭐
   ⭐⭐⭐
    ⭐`;
            break;
    }
    
    if (template) {
        textarea.value += (textarea.value ? '\n\n' : '') + template;
        textarea.focus();
    }
}

function showEmojiPicker() {
    const picker = document.getElementById('emojiPicker');
    picker.classList.toggle('show');
}

function insertEmoji(emoji) {
    const textarea = document.getElementById('postContent');
    textarea.value += emoji;
    textarea.focus();
    document.getElementById('emojiPicker').classList.remove('show');
}

// Close emoji picker when clicking outside
document.addEventListener('click', function(event) {
    const picker = document.getElementById('emojiPicker');
    const btn = event.target.closest('.tool-btn');
    
    if (!picker.contains(event.target) && (!btn || !btn.onclick.toString().includes('showEmojiPicker'))) {
        picker.classList.remove('show');
    }
});

// Initialize when DOM content is loaded
document.addEventListener('DOMContentLoaded', function() {
    connectWebSocket();
    
    // Handle Enter key in textarea (Ctrl+Enter to post)
    document.getElementById('postContent').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
            createPost();
        }
    });
});