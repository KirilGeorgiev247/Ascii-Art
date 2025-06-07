function showTab(tabName) {
  // Hide all tab contents
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.remove("active");
  });

  // Remove active class from all tabs
  document.querySelectorAll(".tab").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Show selected tab content
  document.getElementById(tabName).classList.add("active");

  // Add active class to clicked tab
  if (event && event.target) {
    event.target.classList.add("active");
  }
}

function searchUsers() {
  const query = document.getElementById("searchInput").value;
  if (query.length < 2) {
    document.getElementById("searchResults").innerHTML = `
      <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Search for friends</h3>
        <p>Enter at least 2 characters to search</p>
      </div>
    `;
    return;
  }

  fetch(`/api/users?search=${encodeURIComponent(query)}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && Array.isArray(data.users)) {
        displaySearchResults(data.users);
      } else if (Array.isArray(data)) {
        displaySearchResults(data);
      } else {
        displaySearchResults([]);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      displaySearchResults([]);
    });
}

function displaySearchResults(users) {
  // Filter out users who are already friends or yourself
  const filteredUsers = users.filter(user => !window.friendUserIds.includes(user.id));
  const resultsContainer = document.getElementById("searchResults");

  if (!filteredUsers || filteredUsers.length === 0) {
    resultsContainer.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-user-slash"></i>
        <h3>No users found</h3>
        <p>Try a different search term</p>
      </div>
    `;
    return;
  }

  resultsContainer.innerHTML = filteredUsers
    .map(
      (user) => `
        <div class="friend-card">
          <div class="friend-avatar">
            ${user.username.charAt(0).toUpperCase()}
          </div>
          <div class="friend-name">${user.username}</div>
          <div class="friend-status">
            <i class="fas fa-palette"></i> ASCII Artist
          </div>
          <div class="friend-actions">
            <a href="/profile/${user.id}" class="btn btn-primary">
              <i class="fas fa-eye"></i> View Profile
            </a>
            <button class="btn btn-success" onclick="sendFriendRequest(${user.id})">
              <i class="fas fa-user-plus"></i> Add Friend
            </button>
          </div>
        </div>
      `
    )
    .join("");
}

function sendFriendRequest(userId) {
  fetch("/api/friends/add", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ friend_id: userId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showDialog("Friend request sent!", "success");
      } else {
        showDialog(data.error, "error");
      }
    });
}

function acceptFriend(userId) {
  fetch("/api/friends/accept", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ friend_id: userId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        showDialog(data.error, "error");
      }
    });
}

function rejectFriend(userId) {
  fetch("/api/friends/reject", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ friend_id: userId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload();
      } else {
        showDialog(data.error, "error");
      }
    });
}

function removeFriend(userId) {
  showDialog(
    "Are you sure you want to remove this friend?",
    "question",
    {
      onOk: function () {
        fetch("/api/friends/remove", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ friend_id: userId }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              location.reload();
            } else {
              showDialog(data.error, "error");
            }
          });
      },
      onCancel: function () {
        // Optionally do something if user clicks No
      }
    }
  );
}

document.addEventListener("DOMContentLoaded", function () {
  const viewFriendsBtn = document.getElementById("viewFriendsBtn");
  if (viewFriendsBtn) {
    viewFriendsBtn.addEventListener("click", function () {
      viewFriendsBtn.classList.add("clicked");
    });
  }
});