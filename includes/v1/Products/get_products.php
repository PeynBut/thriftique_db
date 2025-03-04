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

// Fetch product details
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        // Fetch a single product by ID
        $productId = $_GET['id'];
        $stmt = $conn->prepare("SELECT id, name, description, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if ($product) {
            echo json_encode(["success" => true, "product" => $product]);
        } else {
            echo json_encode(["error" => true, "message" => "Product not found"]);
        }
    } else {
        // Fetch all products
        $query = "SELECT id, name, description, price, image FROM products";
        $result = $conn->query($query);
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        echo json_encode(["success" => true, "products" => $products]);
    }
}

$conn->close();
?>
