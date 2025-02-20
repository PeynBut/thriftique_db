<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'C:/xampp2/htdocs/thriftique_db/includes/DBoperations.php';

$db = new DBconnection();
$con = $db->connection();

if ($con->connect_error) {
    die(json_encode(["error" => true, "message" => "Database connection failed: " . $con->connect_error]));
}

// Handle preflight requests (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

// Handle GET request for monthly sales and orders (Analytics data)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS month, 
                SUM(total_price) AS sales, 
                COUNT(id) AS orders 
            FROM orders 
            GROUP BY month 
            ORDER BY month ASC";

    $result = $con->query($sql);
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data); // ✅ Now returns ONLY JSON
    } else {
        echo json_encode(['error' => true, 'message' => 'Query execution failed']);
    }
    exit;
}

// Handle POST request for adding products
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
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
                $stmt = $con->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $name, $description, $price, $imagePath);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
                } else {
                    echo json_encode(['error' => true, 'message' => 'Error adding product to database', 'error_details' => $stmt->error]);
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
    exit;
}

// Close connection
$con->close();
?>
