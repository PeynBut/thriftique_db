<?php
session_start();

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database credentials
$host = 'localhost';
$dbname = 'thriftique';
$username = 'root';
$password = '';

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => true, "message" => "Database connection failed"]));
}

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category = $_POST['category'] ?? ''; 
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;


    // Validate stock as an integer
    if (!is_numeric($stock) || intval($stock) < 0) {
        $_SESSION['toast_message'] = [
            "message" => "Stock must be a non-negative number.",
            "type" => "error"
        ];
        header("Location: products.php");
        exit;
    }
    $stock = intval($stock);

    // Check if the product name already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $_SESSION['toast_message'] = [
            "message" => "Product name already exists. Choose a different name.",
            "type" => "error"
        ];
        header("Location: products.php");
        exit;
    }

    // Check if an image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageExtension, $allowedExtensions)) {
            $uploadDir = 'uploads/';

            // Ensure the 'uploads' directory exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imagePath = $uploadDir . uniqid() . '.' . $imageExtension;

            // Move uploaded file
            if (move_uploaded_file($imageTmpName, $imagePath)) {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsss", $name, $description, $price, $category, $stock, $imagePath);

                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = ['type' => 'success', 'message' => 'Product added successfully'];
                    header("Location: products.php");
                    exit();
                } else {
                    echo json_encode(['error' => true, 'message' => 'Database insertion error']);
                }
                exit();
            } else {
                echo json_encode(['error' => true, 'message' => 'Image upload failed']);
                exit();
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Invalid image type']);
            exit();
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'No image uploaded or upload error']);
        exit();
    }
}

// Fetch products
$products = [];
$query = "SELECT id, name, description, price, category, stock, image FROM products";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="product.css">
    <link rel="stylesheet" href="../Chat/chat.css"> 
    <link rel="stylesheet" href="modal.css">
</head>
<body>
<div class="top-bar">
    <div class="menu-toggle" onclick="toggleMenu()">☰</div>
    <h2 style="padding-top: 20px; padding-left: 20px;">Product Management</h2>

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

<!--drop down menu-->
    <div class="user-menu">
    <div class="user-info" onclick="toggleUserMenu(event)">
        <span id="username">Admin</span> <!-- Placeholder for dynamic name -->
        <i class="fas fa-user-circle"></i>
    </div>
    <div class="user-dropdown" id="userDropdown">
    <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">⚙️ Settings</a>
    <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">🔒 Change Password</a>
    <a href="http://localhost/thriftique_db/includes/v1/admin/logout.php" onclick="logoutUser()">🚪 Logout</a>
    </div>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <a href="http://localhost/thriftique_db/includes/v1//admin/dashboard.html">🏠 Dashboard</a>
    <a href="http://localhost/thriftique_db/includes/v1/Products/products.php">📦 Products</a>
    <a href="http://localhost/thriftique_db/includes/v1/Orders/Order.html">📦 Orders</a>
    <a href="#" onclick="openChat()">💬 Messages</a>
</div>>
    <div class="content" id="content">
        <h2>Products</h2>

        <!-- Product List Table -->
        <div class="products-container">
            <table>
                <thead>
                    <tr>
                    <th>Image</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Category</th> <!-- Added Category Column -->
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    <div id="toast-container"></div>

                    <?php if (!empty($products)) : ?>
                        <?php foreach ($products as $product) : ?>
                            <tr id="product-<?= $product['id'] ?>">
                                <td><img src="<?= htmlspecialchars($product['image']) ?>" width="50"></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td>₱<?= number_format($product['price'], 2) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td> <!-- Display Category -->
                                <td><?= $product['stock'] ?></td> <!-- Display Stock -->
                                <td>
                                    <button onclick="editProduct(<?= $product['id'] ?>)">✏️ Edit</button>
                                    <button onclick="deleteProduct(<?= $product['id'] ?>)">🗑 Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fab" onclick="openProductModal()">+</div>

    <!-- Modal for Product Creation -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <h2>Create New Product</h2>
            <form id="product-form" action="products.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Product Name" required>
                <textarea name="description" placeholder="Product Description" required></textarea>
                <input type="number" name="price" placeholder="Product Price" required>
                
                <!-- Category Selection -->
                <select name="category">
                    <option value="">Select Category</option>
                    <option value="Old School">Old School</option>
                    <option value="Street Wear">Street Wear</option>
                    <option value="Casual Fit">Casual Fit</option>
                </select>
                <input type="number" placeholder="Stock" name="stock" id="stock" min="0" required>


                <input type="file" name="image" accept="image/*" required>
                <button type="submit">Create Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditProductModal()">&times;</span>
            <h2>Edit Product</h2>
            <form id="edit-product-form">
                <input type="hidden" id="edit-product-id">

                <input type="text" id="edit-product-name" placeholder="Product Name" required>

                <textarea id="edit-product-description" placeholder="Product Description" required></textarea>

                <input type="number" id="edit-product-price" placeholder="Product Price" required>

                <!-- ✅ Fixed: Added id="edit-product-category" -->
                <select id="edit-product-category" name="category">
                    <option value="">Select Category</option>
                    <option value="Old School">Old School</option>
                    <option value="Street Wear">Street Wear</option>
                    <option value="Casual Fit">Casual Fit</option>
                </select>

                <!-- ✅ Fixed: Changed id="stock" to id="edit-product-stock" -->
                <input type="number" placeholder="Stock" id="edit-product-stock" min="0" required>

                <input type="file" id="edit-product-image">
                <img id="edit-product-image-preview" src="" width="100" style="display:none;">
                
                <button type="submit">Update Product</button>
            </form>
        </div>
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

        // Function to toggle the sidebar visibility
        function toggleMenu() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("active");
        }

        // Fetch User Details
async function fetchUserDetails() {
    try {
        const response = await fetch("http://localhost/thriftique_db/includes/v1/admin/get_user.php");

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        const usernameElement = document.getElementById("username");

        if (usernameElement) {
            if (data.first_name && data.last_name) {
                usernameElement.textContent = `${data.first_name} ${data.last_name}`;
            } else {
                console.warn("User not found or not logged in.");
                usernameElement.textContent = "Guest";
            }
        }
    } catch (error) {
        console.error("Error fetching user data:", error);
    }
}

// Call fetchUserDetails on DOM content loaded
document.addEventListener("DOMContentLoaded", function () {
    // Fetch user details when the page loads
    fetchUserDetails();

    // Existing code
    fetchNotifications();
    setInterval(checkForNewOrders, 10000); // Poll every 10 seconds
});
            // Toggle User Menu
            function toggleUserMenu(event) {
        event.stopPropagation();
        document.getElementById("userDropdown").classList.toggle("active");
    }
        

    
        // Fetch Notifications
        async function fetchNotifications() {
            try {
                const response = await fetch("http://localhost/thriftique_db/includes/v1/notification/get_notifications.php");
    
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
    
                const data = await response.json();
                const notificationList = document.getElementById("notification-list");
                const notificationCount = document.getElementById("notification-count");
    
                // Save notifications to localStorage
                localStorage.setItem("notifications", JSON.stringify(data));
    
                if (Array.isArray(data) && data.length > 0) {
                    notificationList.innerHTML = data.map(n => `<li>${n.message}</li>`).join("");
                    notificationCount.style.display = "block";
                    notificationCount.innerText = data.length;
                } else {
                    notificationList.innerHTML = "<li>No new notifications</li>";
                    notificationCount.style.display = "none";
                }
            } catch (error) {
                console.error("Error fetching notifications:", error);
    
                const notificationList = document.getElementById("notification-list");
                if (notificationList) {
                    notificationList.innerHTML = "<li>Error loading notifications</li>";
                }
            }
        }

        function logoutUser() {
    fetch("http://localhost/thriftique_db/includes/v1/admin/logout.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Logged out successfully!");
                window.location.href = "http://localhost/thriftique_db/includes/v1/admin/login.php";
            } else {
                alert("Logout failed. Please try again.");
            }
        })
        .catch(error => console.error("Error:", error));
}


        function deleteProduct(productId) {
            if (confirm("Are you sure you want to delete this product?")) {
                fetch("delete_product.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        document.getElementById("product-" + productId).remove(); // Remove row from table
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error("Error:", error));
            }
        }
        // Open the modal
    // Open and Close Product Modal
    function openProductModal() {
        document.getElementById("productModal").style.display = "block";
    }

    function closeProductModal() {
        document.getElementById("productModal").style.display = "none";
    }
    function updateProduct(productId) {
    let formData = new FormData();
    formData.append("action", "update");
    formData.append("id", productId);
    formData.append("name", document.getElementById("edit-product-name").value.trim());
    formData.append("description", document.getElementById("edit-product-description").value.trim());
    formData.append("price", document.getElementById("edit-product-price").value);
    formData.append("stock", document.getElementById("edit-product-stock").value); // ✅ Correct ID for stock
    formData.append("category", document.querySelector("select[name='category']").value); // ✅ Ensure category is included

    const imageInput = document.getElementById("edit-product-image");
    if (imageInput.files.length > 0) {
        formData.append("image", imageInput.files[0]);
    }

    // Debugging: Log FormData before sending
    console.log("🔵 Form Data being sent:");
    for (let pair of formData.entries()) {
        console.log(`${pair[0]}: ${pair[1]}`);
    }

    fetch("http://localhost/thriftique_db/includes/v1/products/update_product.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text()) // Get raw response first
    .then(text => {
        console.log("🔴 Raw Response:", text);
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error("JSON Parse Error: " + error.message + "\nRaw Response: " + text);
        }
    })
    .then(data => {
        console.log("✅ Server Response:", data);
        if (data.success) {
            alert("✅ Product updated successfully!");
            location.reload();
        } else {
            alert("❌ Error: " + data.message);
        }
    })
    .catch(error => console.error("🚨 Fetch Error:", error));
}

// Attach event listener to the form
document.getElementById("edit-product-form").addEventListener("submit", function (event) {
    event.preventDefault();
    updateProduct(document.getElementById("edit-product-id").value);
});

function openEditModal() {
    document.getElementById("editProductModal").style.display = "block";
    document.getElementById("editProductModal").style.zIndex = "1000";
}






    function editProduct(productId) {
    fetch(`http://localhost/thriftique_db/includes/v1/products/get_products.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (!data || data.error) {
                console.error("Invalid product data received:", data);
                return;
            }

            // Check if the response is nested or not
            const product = data.product ? data.product : data;

            document.getElementById("edit-product-id").value = product.id;
            document.getElementById("edit-product-name").value = product.name;
            document.getElementById("edit-product-description").value = product.description;
            document.getElementById("edit-product-price").value = product.price;
            document.getElementById("stock").value = product.stock; // Use correct stock field ID
            
            // Set selected category
            document.querySelector(`select[name="category"]`).value = product.category;

            // Handle image preview
            if (product.image) {
                document.getElementById("edit-product-image-preview").src = product.image;
                document.getElementById("edit-product-image-preview").style.display = "block";
            } else {
                document.getElementById("edit-product-image-preview").style.display = "none";
            }

            // Show the modal
            document.getElementById("editProductModal").style.display = "block";
        })
        .catch(error => console.error("Error fetching product:", error));

    // Close modal when clicking outside
    window.onclick = function(event) {
        let modal = document.getElementById("editProductModal");
        if (event.target === modal) {
            closeEditProductModal();
        }
    };
}

function closeEditProductModal() {
    document.getElementById("editProductModal").style.display = "none";
}


// Add new product to the table
function addProductToTable(product) {
    const tableBody = document.querySelector('.products-container tbody');
    const newRow = document.createElement('tr');
    newRow.id = `product-${product.id}`;
    newRow.innerHTML = `
        <td><img src="${product.image}" width="50"></td>
        <td>${product.name}</td>
        <td>${product.description}</td>
        <td>$${parseFloat(product.price).toFixed(2)}</td>
        <td>${product.category ? product.category : 'N/A'}</td> <!-- Display category -->
        <td>${product.stock}</td> <!-- Add stock column -->
        <td>
            <button onclick="editProduct(${product.id})">✏️ Edit</button>
            <button onclick="deleteProduct(${product.id})">🗑 Delete</button>
        </td>
    `;
    tableBody.insertBefore(newRow, tableBody.firstChild); // Add at the top
}

    // Fetch Notifications
async function fetchNotifications() {
    try {
        const response = await fetch("http://localhost/thriftique_db/includes/v1/notification/get_notifications.php");

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        const notificationList = document.getElementById("notification-list");
        const notificationCount = document.getElementById("notification-count");

        if (Array.isArray(data) && data.length > 0) {
            notificationList.innerHTML = data.map(n => `<li>${n.message}</li>`).join("");
            notificationCount.style.display = "block";
            notificationCount.innerText = data.length;
        } else {
            notificationList.innerHTML = "<li>No new notifications</li>";
            notificationCount.style.display = "none";
        }
    } catch (error) {
        console.error("Error fetching notifications:", error);

        const notificationList = document.getElementById("notification-list");
        if (notificationList) {
            notificationList.innerHTML = "<li>Error loading notifications</li>";
        }
    }
}
let lastOrderId = 0; // Track the last order ID
let hasFetched = false; // Flag to ensure the fetch only happens once

// Fetch Notifications from backend
async function fetchNotifications() {
    try {
        const response = await fetch("http://localhost/thriftique_db/includes/v1/notification/get_notifications.php");

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        const notificationList = document.getElementById("notification-list");
        const notificationCount = document.getElementById("notification-count");

        if (Array.isArray(data) && data.length > 0) {
            notificationList.innerHTML = data.map(n => `<li>${n.message}</li>`).join("");
            notificationCount.style.display = "block";
            notificationCount.innerText = data.length;
        } else {
            notificationList.innerHTML = "<li>No new notifications</li>";
            notificationCount.style.display = "none";
        }
    } catch (error) {
        console.error("Error fetching notifications:", error);
        const notificationList = document.getElementById("notification-list");
        if (notificationList) {
            notificationList.innerHTML = "<li>Error loading notifications</li>";
        }
    }
}

// Display notifications stored in localStorage
function displayStoredNotifications() {
    const notificationList = document.getElementById("notification-list");
    const notificationCount = document.getElementById("notification-count");

    const notifications = JSON.parse(localStorage.getItem("notifications")) || [];

    if (notifications.length > 0) {
        notificationList.innerHTML = notifications.map(n => `<li>${n.message}</li>`).join("");
        notificationCount.style.display = "block";
        notificationCount.innerText = notifications.length;
    } else {
        notificationList.innerHTML = "<li>No new notifications</li>";
        notificationCount.style.display = "none";
    }
}

// Check for new orders (only fetch once)
async function checkForNewOrders() {
    if (hasFetched) return; // Prevent further fetching once it's been called

    try {
        const response = await fetch("http://localhost/thriftique_db/includes/v1/notification/check_new_orders.php");

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const newOrderData = await response.json();
        console.log("Fetched new orders: ", newOrderData); // Debugging line

        const notificationList = document.getElementById("notification-list");
        const notificationCount = document.getElementById("notification-count");

        if (newOrderData && newOrderData.length > 0) {
            // Filter out orders that have already been notified
            const filteredOrders = newOrderData.filter(order => order.order_id > lastOrderId);
            console.log("Filtered orders: ", filteredOrders); // Debugging line

            filteredOrders.forEach(order => {
                const fullName = order.full_name || "Unknown User";
                const message = `New order from ${fullName}: Order ID ${order.order_id}`;
                const listItem = document.createElement("li");
                listItem.innerHTML = message;
                notificationList.prepend(listItem); // Prepend to show new orders at the top
                notificationCount.style.display = "block";
                notificationCount.innerText = parseInt(notificationCount.innerText) + 1;

                // Update the last order ID
                lastOrderId = order.order_id;
            });
        } else {
            console.log("No new orders");
        }

        // Set the flag to true to prevent future fetches
        hasFetched = true;
    } catch (error) {
        console.error("Error checking for new orders:", error);
    }
}

// Poll for new orders (once) every 3 seconds
setInterval(() => {
    checkForNewOrders();
}, 3000); // Will fetch once and then stop

// Toggle Notifications Dropdown
function toggleNotifications() {
    const notificationDropdown = document.getElementById("notifications");
    notificationDropdown.classList.toggle("show");
}

// Toggle User Menu
function toggleUserMenu(event) {
    event.stopPropagation();
    document.getElementById("userDropdown").classList.toggle("active");
}

// Fetch notifications and display when the page loads
document.addEventListener("DOMContentLoaded", function () {
    // Attach the event listener to the notification bell icon after DOM is loaded
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        notificationBell.addEventListener('click', toggleNotifications);
    }

    // Fetch notifications when the page loads
    fetchNotifications();

    // Display any stored notifications from localStorage
    displayStoredNotifications();
});
 // Fetch notifications, user details, and periodically check for new orders
 document.addEventListener("DOMContentLoaded", function () {
            // Attach the event listener to the notification bell icon after DOM is loaded
            const notificationBell = document.querySelector('.notification-bell');
            if (notificationBell) {
                notificationBell.addEventListener('click', toggleNotifications);
            }
    
            // Fetch notifications when the page loads
            fetchNotifications();
    
            // Fetch user details when the page loads
            fetchUserDetails();
    
            // Check for new orders every 10 seconds
            setInterval(checkForNewOrders, 10000); // Poll every 10 seconds
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
function showToast(message, type = "success") {
    const toastContainer = document.getElementById("toast-container");
    const toast = document.createElement("div");
    toast.className = `toast ${type}`;
    toast.innerHTML = `${message} <button onclick="this.parentElement.remove()">×</button>`;
    
    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000); // Remove after 3s
}

// Check if there's a toast message from PHP
document.addEventListener("DOMContentLoaded", function () {
    <?php if (isset($_SESSION['toast_message'])) : ?>
        showToast("<?= $_SESSION['toast_message']['message'] ?>", "<?= $_SESSION['toast_message']['type'] ?>");
        <?php unset($_SESSION['toast_message']); ?>
    <?php endif; ?>
});
// Auto-fetch notifications every 5 seconds
setInterval(fetchNotifications, 5000);
fetchNotifications();


    </script>
</body>
</html>
