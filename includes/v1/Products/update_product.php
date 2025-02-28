<?php
session_start();

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'] ?? '';
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
            $imagePath = $uploadDir . uniqid() . '.' . $imageExtension;

            // Move uploaded file
            if (move_uploaded_file($imageTmpName, $imagePath)) {
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ? WHERE id = ?");
                $stmt->bind_param("ssdsi", $name, $description, $price, $imagePath, $id);
            } else {
                echo json_encode(['error' => true, 'message' => 'Image upload failed']);
                exit();
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Invalid image type']);
            exit();
        }
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $name, $description, $price, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['error' => true, 'message' => 'Database update error']);
    }
    exit();
}

$conn->close();
?>