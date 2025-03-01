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
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';

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
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $name, $description, $price, $imagePath);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
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

// Fetch products from the database
$sql = "SELECT id, name, description, price, image FROM products ORDER BY id DESC";
$result = $conn->query($sql);
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
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
</head>
<body>
<div class="top-bar">
    <div class="menu-toggle" onclick="toggleMenu()">☰</div>
    <h2 style="padding-top: 20px; padding-left: 20px;">Product Management</h2>

    <!-- Notification Bell -->
    <div class="notification-container" onclick="toggleNotifications()">
        <i class="fas fa-bell notification-bell"></i>
        <span class="badge" id="notification-count">0</span>
    </div>

    <!-- Notifications Dropdown -->
    <div class="notification-dropdown" id="notification-dropdown">
        <h4>Notifications</h4>
        <ul id="notification-list">
            <li>No new notifications</li>
        </ul>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <a href="http://localhost/thriftique_db/includes/v1//admin/dashboard.html">🏠 Dashboard</a>
    <a href="http://localhost/thriftique_db/includes/v1/Products/products.php">📦 Products</a>
    <a href="http://localhost/thriftique_db/includes/v1/Orders/Order.html">📦 Orders</a>
    <a href="http://localhost/thriftique_db/includes/v1/Categories/Categories.php">📂 Categories</a>
    <a href="#" onclick="openChat()">💬 Messages</a>
    <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">⚙️ Settings</a>
    <a href="http://localhost/thriftique_db/includes/v1/admin/logout.php" class="logout" onclick="logoutUser()">🚪 Logout</a>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)) : ?>
                        <?php foreach ($products as $product) : ?>
                            <tr id="product-<?= $product['id'] ?>">
                                <td><img src="<?= htmlspecialchars($product['image']) ?>" width="50"></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['description']) ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td>
                                <button onclick="editProduct(<?= $product['id'] ?>)">✏️ Edit</button>
                                <button onclick="deleteProduct(<?= $product['id'] ?>)">🗑 Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5">No products found.</td>
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
                <input type="file" name="image" accept="image/*" required>
                <button type="submit">Create Product</button>
            </form>
        </div>
    </div>
    <!-- Add this inside the <body> -->
<!-- Edit Product Modal -->
<!-- Modal for Editing Product -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditProductModal()">&times;</span>
        <h2>Edit Product</h2>
        <form id="edit-product-form" action="update_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-product-id">
            <input type="text" name="name" id="edit-product-name" placeholder="Product Name" required>
            <textarea name="description" id="edit-product-description" placeholder="Product Description" required></textarea>
            <input type="number" name="price" id="edit-product-price" placeholder="Product Price" required>
            <input type="file" name="image" accept="image/*">
            <button type="submit">Update Product</button>
        </form>
    </div>
</div>
</body>
</html>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('shift');
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
function openProductModal() {
    document.getElementById('productModal').style.display = 'block';
}

// Close the modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Handle form submission
document.getElementById('product-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('products.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeProductModal();
            addProductToTable(data.product); // Add the new product to the table
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});

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
        <td>
            <button onclick="deleteProduct(${product.id})">🗑 Delete</button>
        </td>
    `;
    tableBody.insertBefore(newRow, tableBody.firstChild); // Add at the top
}
function toggleNotifications() {
    document.getElementById('notification-dropdown').classList.toggle('show');
}

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

// Auto-fetch notifications every 5 seconds
setInterval(fetchNotifications, 5000);
fetchNotifications();

    </script>
</body>
</html>
