let searchTimeout;
let currentFilter = 'all';

// Initialize filter buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;

            const searchInput = document.getElementById('searchInput');
            if (searchInput.value.length >= 2) {
                searchUsers();
            } else {
                loadUsersByFilter(currentFilter);
            }
        });
    });

    loadUsersByFilter('all');
});

function searchUsers() {
    const query = document.getElementById("searchInput").value;
    if (query.length < 2) {
        showEmptyState();
        return;
    }

    fetch(`/api/users?search=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                displayUsers(data);
            } else if (data.users && Array.isArray(data.users)) {
                displayUsers(data.users);
            } else {
                displayUsers([]);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showError("Failed to search users");
        });
}

function loadUsersByFilter(filter) {
    showLoading();

    fetch(`/api/users/filter/${filter}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUsers(data.users);
        } else {
            showError(data.error || 'Failed to load users');
        }
    })
    .catch(error => {
        console.error('Filter error:', error);
        showError('Network error occurred');
    });
}

function displayUsers(users) {
    const container = document.getElementById('usersContainer');

    if (users.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>No users found</h3>
                <p>Try adjusting your search terms or filters</p>
            </div>
        `;
        return;
    }

    container.innerHTML = users.map(user => `
        <div class="user-card">
            <div class="user-avatar">
                ${user.username.charAt(0).toUpperCase()}
            </div>
            <div class="user-info">
                <div class="user-name">${escapeHtml(user.username)}</div>
                ${user.bio ? `<div class="user-bio">${escapeHtml(user.bio)}</div>` : ''}
                ${getFriendshipStatusBadge(user.friendship_status)}
            </div>
            <div class="user-stats">
                <div class="stat">
                    <span class="stat-number">${user.posts_count || 0}</span>
                    <span>Posts</span>
                </div>
                <div class="stat">
                    <span class="stat-number">${user.friends_count || 0}</span>
                    <span>Friends</span>
                </div>
                <div class="stat">
                    <span class="stat-number">${user.likes_count || 0}</span>
                    <span>Likes</span>
                </div>
            </div>
            <div class="user-actions">
                <a href="/profile/${user.id}" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Profile
                </a>
                ${getFriendActionButton(user)}
            </div>
        </div>
    `).join('');
}

function getFriendshipStatusBadge(status) {
    switch(status) {
        case 'friends':
            return '<span class="friendship-status status-friends"><i class="fas fa-check"></i> Friends</span>';
        case 'pending':
            return '<span class="friendship-status status-pending"><i class="fas fa-clock"></i> Pending</span>';
        default:
            return '<span class="friendship-status status-none"><i class="fas fa-user-plus"></i> Not connected</span>';
    }
}

function getFriendActionButton(user) {
    switch(user.friendship_status) {
        case 'friends':
            return `<button class="btn btn-secondary" onclick="removeFriend(${user.id})">
                <i class="fas fa-user-minus"></i> Remove
            </button>`;
        case 'pending':
            return `<button class="btn btn-secondary" disabled>
                <i class="fas fa-clock"></i> Pending
            </button>`;
        default:
            return `<button class="btn btn-success" onclick="sendFriendRequest(${user.id})">
                <i class="fas fa-user-plus"></i> Add Friend
            </button>`;
    }
}

function showLoading() {
    document.getElementById('usersContainer').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Searching...</p>
        </div>
    `;
}

function showEmptyState() {
    document.getElementById('usersContainer').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h3>Start your search</h3>
            <p>Enter at least 2 characters to search for users</p>
        </div>
    `;
}

function showError(message) {
    document.getElementById('usersContainer').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error</h3>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
}

function sendFriendRequest(userId) {
    fetch('/api/friends/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the button state
            event.target.outerHTML = `
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-clock"></i> Pending
                </button>
            `;
            showNotification('Friend request sent!', 'success');
        } else {
            showNotification('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
    });
}

function removeFriend(userId) {
    if (confirm('Are you sure you want to remove this friend?')) {
        fetch('/api/friends/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ userId: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the button state
                event.target.outerHTML = `
                    <button class="btn btn-success" onclick="sendFriendRequest(${userId})">
                        <i class="fas fa-user-plus"></i> Add Friend
                    </button>
                `;
                showNotification('Friend removed', 'success');
            } else {
                showNotification('Error: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error occurred', 'error');
        });
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 1000;
        animation: slideIn 0.3s ease;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}