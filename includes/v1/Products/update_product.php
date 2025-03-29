<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization");
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'thriftique';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => true, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Handle CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// ✅ DEBUG: Log received data
file_put_contents("debug_log.txt", "Received POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents("debug_log.txt", "Received FILES Data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

// ✅ Check for POST request and required parameters
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => '❌ Request is not POST']);
    exit();
}

if (empty($_POST)) {
    echo json_encode(['error' => true, 'message' => '❌ No POST data received.']);
    exit();
}

if (!isset($_POST['action'])) {
    echo json_encode(['error' => true, 'message' => '❌ Missing action parameter']);
    exit();
}

if ($_POST['action'] !== 'update') {
    echo json_encode(['error' => true, 'message' => '❌ Invalid action']);
    exit();
}

// Retrieve form inputs safely
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

if ($id <= 0 || empty($name) || empty($description) || $price <= 0 || $stock < 0 || empty($category)) {
    echo json_encode(['error' => true, 'message' => '❌ All fields are required']);
    exit();
}

// ✅ Update product details (excluding image)
$query = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ? WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => true, 'message' => 'SQL Prepare Error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ssdiss", $name, $description, $price, $stock, $category, $id);
if (!$stmt->execute()) {
    echo json_encode(['error' => true, 'message' => 'Database update error: ' . $stmt->error]);
    exit();
}

// ✅ Handle image upload if provided
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageTmpName = $_FILES['image']['tmp_name'];
    $imageName = $_FILES['image']['name'];
    $imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageExtension, $allowedExtensions)) {
        echo json_encode(['error' => true, 'message' => 'Invalid image type']);
        exit();
    }

    // Ensure the upload directory exists
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate a unique image filename
    $imagePath = $uploadDir . uniqid() . '.' . $imageExtension;
    if (!move_uploaded_file($imageTmpName, $imagePath)) {
        echo json_encode(['error' => true, 'message' => 'Image upload failed']);
        exit();
    }

    // ✅ Log image upload
    file_put_contents("debug_log.txt", "Image uploaded to: " . $imagePath . "\n", FILE_APPEND);

    // ✅ Update product image in database
    $query = "UPDATE products SET image = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['error' => true, 'message' => 'SQL Prepare Error: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("si", $imagePath, $id);
    if (!$stmt->execute()) {
        echo json_encode(['error' => true, 'message' => 'Database image update error: ' . $stmt->error]);
        exit();
    }
}

// ✅ Success response
echo json_encode(['success' => true, 'message' => '✅ Product updated successfully']);

// Close statement and database connection
$stmt->close();
$conn->close();
exit();
?>
