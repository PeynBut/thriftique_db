<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "../../DBconnection.php";

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Select data for the last 30 days (last month)
    $sql = "SELECT 
                DATE(created_at) AS date,  -- This will group by specific date (day)
                SUM(total_price) AS sales, 
                COUNT(id) AS orders 
            FROM orders 
            WHERE created_at >= NOW() - INTERVAL 1 MONTH  -- Get the last 30 days (last month)
            GROUP BY date  -- Group by date to get individual days
            ORDER BY date ASC"; // Ascending order by date (from oldest to most recent)

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
    // Ensure required fields are passed in the POST request
    if (!isset($_POST['name']) || !isset($_POST['description']) || !isset($_POST['price'])) {
        echo json_encode(['error' => true, 'message' => 'Missing required fields (name, description, or price)']);
        exit;
    }

    // Sanitize and collect form data
    $name = htmlspecialchars(trim($_POST['name']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']); // Ensure price is numeric

    // Check if image is provided and handle upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageSize = $_FILES['image']['size'];
        $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);

        // Validate image type and size
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxImageSize = 5 * 1024 * 1024; // 5MB

        if (in_array(strtolower($imageExtension), $allowedExtensions)) {
            if ($imageSize <= $maxImageSize) {
                $imagePath = 'uploads/' . uniqid() . '.' . $imageExtension;

                // Move the uploaded image to the server's upload directory
                if (move_uploaded_file($imageTmpName, $imagePath)) {
                    // Insert product details into the database
                    $stmt = $con->prepare("INSERT INTO products (name, description, price, image, date) VALUES (?, ?, ?, ?, CURDATE())");
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
                echo json_encode(['error' => true, 'message' => 'Image exceeds the maximum size of 5MB']);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Invalid image type. Only JPG, JPEG, PNG, GIF are allowed']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Image is required']);
    }
    exit;
}

// Close connection
$con->close();
?>
