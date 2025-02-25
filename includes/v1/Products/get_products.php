<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Database credentials
$host = 'localhost';
$dbname = 'thriftique';
$username = 'root';
$password = '';

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => true, "message" => "Database connection failed"]);
    exit;
}

// Base URL for images
$baseURL = "http://192.168.100.184/thriftique_db/includes/v1/Products/uploads/"; // Adjust to your local server

// Fetch products
$sql = "SELECT id, name, description, price, image FROM products ORDER BY id DESC";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(["error" => true, "message" => "Failed to fetch products"]);
    $conn->close();
    exit;
}

$products = [];

while ($row = $result->fetch_assoc()) {
    $imagePath = trim($row["image"]); // Remove unwanted spaces

    // Ensure correct image path
    if (!empty($imagePath)) {
        $imageURL = $baseURL . basename($imagePath);
    } else {
        $imageURL = $baseURL . "default.png"; // Default image if none found
    }

    $products[] = [
        "id" => (int) $row["id"],
        "name" => $row["name"],
        "description" => $row["description"],
        "price" => (float) $row["price"],
        "image" => $imageURL
    ];
}

// Send JSON response
echo json_encode($products, JSON_PRETTY_PRINT);
$conn->close();
?>
