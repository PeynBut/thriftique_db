<?php
session_start();

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

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

// Define base URL for images
$base_url = "http://192.168.100.184/thriftique_db/includes/v1/Products/uploads/";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch a single product by ID
        $productId = $_GET['id'];
        $stmt = $conn->prepare("SELECT id, name, description, price, image, stock, category FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            // ✅ Ensure full image URL
            if (!empty($product['image']) && !filter_var($product['image'], FILTER_VALIDATE_URL)) {
                $product['image'] = $base_url . basename($product['image']);
            }
            echo json_encode(["success" => true, "product" => $product]);
        } else {
            echo json_encode(["error" => true, "message" => "Product not found"]);
        }
    } else {
        // Fetch all products or filter by category
        $category = isset($_GET['category']) ? $_GET['category'] : "";

        if (!empty($category) && $category !== "All") {
            $stmt = $conn->prepare("SELECT id, name, description, price, image, stock, category FROM products WHERE category = ?");
            $stmt->bind_param("s", $category);
        } else {
            $stmt = $conn->prepare("SELECT id, name, description, price, image, stock, category FROM products");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            // ✅ Ensure full image URL
            if (!empty($row['image']) && !filter_var($row['image'], FILTER_VALIDATE_URL)) {
                $row['image'] = $base_url . basename($row['image']);
            }
            $products[] = $row;
        }

        echo json_encode(["success" => true, "products" => $products]);
    }
}

$conn->close();
?>
