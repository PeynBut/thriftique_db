<?php
require_once '../../DBoperations.php';

$db = new DBoperations();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
    $response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'create':
                    if (!empty($data['name']) && !empty($data['description'])) {
                        $result = $db->createCategory($data['name'], $data['description']);
                        $response['error'] = $result != 1;
                        $response['message'] = $result == 1 ? "Category created successfully" : "Failed to create category";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Required fields are missing";
                    }
                    break;
                case 'update':
                    if (!empty($data['id']) && !empty($data['name']) && !empty($data['description'])) {
                        $result = $db->updateCategory($data['id'], $data['name'], $data['description']);
                        $response['error'] = $result != 1;
                        $response['message'] = $result == 1 ? "Category updated successfully" : "Failed to update category";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Required fields are missing";
                    }
                    break;
                case 'delete':
                    if (!empty($data['id'])) {
                        $result = $db->deleteCategory($data['id']);
                        $response['error'] = $result != 1;
                        $response['message'] = $result == 1 ? "Category deleted successfully" : "Failed to delete category";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Required fields are missing";
                    }
                    break;
                default:
                    $response['error'] = true;
                    $response['message'] = "Invalid action";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Action not specified";
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $categories = $db->getCategories();
        $response['error'] = false;
        $response['categories'] = array();
        while ($category = $categories->fetch_assoc()) {
            array_push($response['categories'], $category);
        }
    }

    echo json_encode($response);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thriftique Categories</title>
    <link rel="stylesheet" href="../Chat/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: #f8f9fa;
        }

        .top-bar {
            width: 100%;
            height: 60px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .menu-toggle {
            font-size: 24px;
            cursor: pointer;
        }

        .sidebar {
            width: 250px;
            background: #ffffff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            position: fixed;
            left: -260px;
            top: 60px;
            height: calc(100% - 60px);
            transition: left 0.3s ease-in-out;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar a {
            text-decoration: none;
            color: #333;
            padding: 12px;
            display: block;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background: #e9ecef;
        }

        .content {
            flex: 1;
            padding: 80px 20px 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        .content.shift {
            margin-left: 250px;
        }

        .categories-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ddd;
        }

        .add-btn {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
        }

        .add-btn:hover {
            background: #218838;
        }
/* Notification Container */
.notification-container {
    position: relative;
    margin-left: auto;
    cursor: pointer;
    padding: 10px 15px;
}

/* Notification Bell */
.notification-bell {
    font-size: 24px;
    color: #333; /* Slightly softer black */
    transition: color 0.3s ease;
}

.notification-container:hover .notification-bell {
    color: #007bff; /* Highlight on hover */
}

/* Notification Badge */
.badge {
    background: red;
    color: white;
    font-size: 12px;
    padding: 3px 7px;
    border-radius: 50%;
    position: absolute;
    top: -5px;
    right: -5px;
    display: none;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    right: 0;
    top: 50px;
    background: white;
    width: 320px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    display: none;
    z-index: 1000;
    transform: translateY(-10px);
    opacity: 0;
    transition: all 0.3s ease;
}

/* Show Dropdown with Smooth Animation */
.notification-dropdown.show {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

/* Dropdown Header */
.notification-dropdown h4 {
    padding: 12px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    font-size: 16px;
    font-weight: bold;
    margin: 0;
}

/* Notification List */
.notification-dropdown ul {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 250px;
    overflow-y: auto;
}

/* Notification Items */
.notification-dropdown li {
    padding: 12px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.notification-dropdown li:hover {
    background: #f8f9fa;
}

.notification-dropdown li:last-child {
    border-bottom: none;
}

/* Sidebar Logout Button */
.sidebar a.logout {
    color: red;
    font-weight: bold;
    margin-top: 20px;
    display: block;
    text-align: center;
    padding: 10px;
    border-radius: 5px;
    transition: background 0.3s ease;
}

.sidebar a.logout:hover {
    background-color: #ffdddd;
}

/* Logout Icon Fix */
.sidebar a.logout i {
    margin-right: 5px;
}

/* Mobile Optimization */
@media (max-width: 768px) {
    .notification-dropdown {
        width: 90%;
        right: 5px;
    }
}
/* User Info in Top Bar */
.user-menu {
    position: relative;
    display: flex;
    align-items: center;
    margin-right: 20px;
    cursor: pointer;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    color: #333;
    font-weight: 500;
}

.user-info i {
    font-size: 22px;
    color: #333;
}

/* User Dropdown */
.user-dropdown {
    position: absolute;
    right: 0;
    top: 40px;
    background: #fff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 6px;
    width: 150px;
    display: none;
    flex-direction: column;
    z-index: 10;
}

.user-dropdown a {
    padding: 10px;
    text-decoration: none;
    color: #333;
    display: block;
    font-size: 14px;
}

.user-dropdown a:hover {
    background: #f4f4f4;
}

.user-dropdown.active {
    display: flex;
}


    </style>
</head>
<body>

  <!-- Top Navigation Bar -->
<div class="top-bar">
    <div class="menu-toggle" onclick="toggleMenu()">☰</div>
    <h2 style="margin-left: 20px;">Thriftique Categories</h2>

    <!-- Notification Icon -->
    <div class="notification-container" onclick="toggleNotifications()">
        <i class="fas fa-bell notification-bell" aria-label="Notifications"></i>
        <span class="badge" id="notification-count">0</span>

        <!-- Notifications Dropdown -->
        <div class="notification-dropdown" id="notifications">
            <h4>Notifications</h4>
            <ul id="notification-list">
                <li>No new notifications</li>
            </ul>
        </div>
    </div>
    <div class="user-menu">
        <div class="user-info" onclick="toggleUserMenu()">
            <span id="username">Admin</span> <!-- Placeholder for dynamic name -->
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-dropdown" id="userDropdown">
            <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">⚙️ Settings</a>
            <a href="http://localhost/thriftique_db/includes/v1/admin/logout.php" onclick="logoutUser()">🚪 Logout</a>
            <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">🔒 Change Password</a>
        </div>
    </div> 
  </div>
</div>


<div class="sidebar" id="sidebar">
    <a href="http://localhost/thriftique_db/includes/v1//admin/dashboard.html">🏠 Dashboard</a>
    <a href="http://localhost/thriftique_db/includes/v1/Products/products.php">📦 Products</a>
    <a href="http://localhost/thriftique_db/includes/v1/Orders/Order.html">📦 Orders</a>
    <a href="http://localhost/thriftique_db/includes/v1/Categories/Categories.php">📂 Categories</a>
    <a href="#" onclick="openChat()">💬 Messages</a>
</div>     
    <div class="content" id="content">
        <h2>Product Categories</h2>
        <button class="add-btn" onclick="addCategory()">➕ Add Category</button>
        <div id="category-list" class="categories-overview"></div>
    </div>

    
      <!-- Chat Section (Hidden Initially) -->
      <div id="chat-section" class="chat-box">
        <div class="chat-header">
            <h3>Live Chat</h3>
            <button onclick="closeChat()">✖</button>
        </div>
        <div id="chat-messages" class="chat-messages"></div>
        <div class="chat-input">
            <input type="text" id="chatMessage" placeholder="Type a message..." />
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
      
 
    
    <script>
document.addEventListener("DOMContentLoaded", function () {
    fetchCategories();
});

function fetchCategories() {
    fetch("Categories.php")
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                const container = document.getElementById("category-list");
                container.innerHTML = ""; // Clear previous content

                data.categories.forEach(category => {
                    let div = document.createElement("div");
                    div.classList.add("card");
                    div.innerHTML = `
                        <h3>${category.name}</h3>
                        <p>${category.description}</p>
                        <button class='edit-btn' onclick='editCategory(${category.id}, "${category.name}", "${category.description}")'>✏️ Edit</button>
                        <button class='delete-btn' onclick='deleteCategory(${category.id})'>🗑 Delete</button>
                    `;
                    container.appendChild(div);
                });
            }
        });
}

function addCategory() {
    let name = prompt("Enter new category name:");
    let description = prompt("Enter category description:");
    if (name && description) {
        fetch("Categories.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "create", name, description })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            fetchCategories();
        });
    }
}

function editCategory(id, name, description) {
    let newName = prompt("Edit category name:", name);
    let newDescription = prompt("Edit category description:", description);
    if (newName && newDescription) {
        fetch("categories.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "update", id, name: newName, description: newDescription })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            fetchCategories();
        });
    }
}

function deleteCategory(id) {
    if (confirm("Are you sure you want to delete this category?")) {
        fetch("Categories.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "delete", id })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            fetchCategories();
        });
    }
}   
    //sidebar
function toggleMenu() {
    let sidebar = document.getElementById('sidebar');
    let content = document.getElementById('content');
    sidebar.classList.toggle('active'); // Toggle sidebar
    content.classList.toggle('shift'); // Shift content       
}
function toggleNotifications() {
            document.getElementById('notifications').classList.toggle('show');
        }
        //Notification
        function fetchNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    let notificationList = document.getElementById('notification-list');
                    let notificationCount = document.getElementById('notification-count');

                    notificationList.innerHTML = "";
                    if (data.length > 0) {
                        notificationCount.style.display = "block";
                        notificationCount.innerText = data.length;

                        data.forEach(notification => {
                            let li = document.createElement('li');
                            li.innerText = notification.message;
                            notificationList.appendChild(li);
                        });
                    } else {
                    notificationList.innerHTML = "<li>No new notifications</li>";
                    notificationCount.style.display = "none";
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

            setInterval(fetchNotifications, 5000); // Fetch every 5 seconds
            fetchNotifications(); // Load initially
    // User Dropdown
    function toggleUserMenu() {
        document.getElementById("userDropdown").classList.toggle("active");
    }
    
    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        const dropdown = document.getElementById("userDropdown");
        if (!event.target.closest(".user-menu")) {
            dropdown.classList.remove("active");
        }
    });
    document.addEventListener("DOMContentLoaded", async function () {
        try {
            const response = await fetch("http://localhost/thriftique_db/includes/v1/admin/get_user.php");
            const data = await response.json();
    
            if (data.first_name) {
                document.getElementById("username").textContent = `${data.first_name} ${data.last_name}`;
            } else {
                console.warn("User not found or not logged in");
            }
        } catch (error) {
            console.error("Error fetching user data:", error);
        }
    });    
    function openChat() {
        document.getElementById('chat-section').style.display = "block";
    }

    function closeChat() {
        document.getElementById('chat-section').style.display = "none";
    }

    // WebSocket Connection
    let ws;

function connectWebSocket() {
ws = new WebSocket("ws://localhost:8080");

ws.onopen = function () {
    console.log("Connected to WebSocket");
};

ws.onmessage = function (event) {
    console.log("WebSocket message received:", event.data);

    const chatMessages = document.getElementById("chat-messages");
    if (!chatMessages) {
        console.error("Chat messages container not found!");
        return;
    }

    const data = JSON.parse(event.data);
    if (!data.message) {
        console.error("Invalid message format received:", data);
        return;
    }

    const message = document.createElement("div");
    message.classList.add("message");
    message.textContent = `${data.sender}: ${data.message}`;

    chatMessages.appendChild(message);
    chatMessages.scrollTop = chatMessages.scrollHeight;
};

ws.onclose = function () {
    console.warn("WebSocket closed. Reconnecting in 3 seconds...");
    setTimeout(connectWebSocket, 3000); // Reconnect after 3s
};

ws.onerror = function (error) {
    console.error("WebSocket error:", error);
    ws.close();
};
}

// Start WebSocket connection
connectWebSocket();

function sendMessage() {
const input = document.getElementById("chatMessage");
const message = input.value.trim();

if (!message) {
    console.warn("Cannot send an empty message.");
    return;
}

if (ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify({ sender: "Admin", receiver: "User", message: message }));
    input.value = "";
} else {
    console.error("WebSocket is not open. Cannot send message.");
}
}
    </script>
</body>
</html>
