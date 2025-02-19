<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection (update with your details)
$host = 'localhost';
$dbname = 'thriftique';
$username = 'root';
$password = '';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    // Sanitize and collect form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageSize = $_FILES['image']['size'];
        $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);

        // Check for valid image file types (e.g., jpg, png)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($imageExtension), $allowedExtensions)) {
            $imagePath = 'uploads/' . uniqid() . '.' . $imageExtension;

            // Move the uploaded image to the server's upload directory
            if (move_uploaded_file($imageTmpName, $imagePath)) {
                // Insert product details into the database
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $name, $description, $price, $imagePath);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
                } else {
                    echo json_encode(['error' => true, 'message' => 'Error adding product to database']);
                }
            } else {
                echo json_encode(['error' => true, 'message' => 'Image upload failed']);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Invalid image type']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Image is required']);
    }
} else {
    echo json_encode(['error' => true, 'message' => 'Invalid request']);
}

$conn->close();
?>
