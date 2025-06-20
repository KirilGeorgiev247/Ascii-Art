function showTab(tabName) {
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.remove("active");
  });

  document.querySelectorAll(".tab").forEach((tab) => {
    tab.classList.remove("active");
  });

  document.getElementById(tabName).classList.add("active");

  document.querySelectorAll(".tab").forEach((tab) => {
    if (
      tab.getAttribute("onclick") &&
      tab.getAttribute("onclick").includes(`showTab('${tabName}'`)
    ) {
      tab.classList.add("active");
    }
  });
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
  const filteredUsers = users.filter(
    (user) => !window.friendUserIds.includes(user.id)
  );
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
    .map((user) => {
      let actionButton = "";
      if (user.friendship_status === "pending") {
        actionButton = `<button class="btn btn-secondary" disabled>
            <i class="fas fa-clock"></i> Sent
          </button>`;
      } else {
        actionButton = `<button class="btn btn-success" onclick="sendFriendRequest(${user.id})">
            <i class="fas fa-user-plus"></i> Add Friend
          </button>`;
      }
      return `
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
            ${actionButton}
          </div>
        </div>
      `;
    })
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
        searchUsers();
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
  showDialog("Are you sure you want to remove this friend?", "question", {
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
    onCancel: function () {},
  });
}

document.addEventListener("DOMContentLoaded", function () {
  const viewFriendsBtn = document.getElementById("viewFriendsBtn");
  if (viewFriendsBtn) {
    viewFriendsBtn.addEventListener("click", function () {
      viewFriendsBtn.classList.add("clicked");
    });
  }
});
