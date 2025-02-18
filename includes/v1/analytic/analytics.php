<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include necessary files from the parent directory
require_once '../../../DBoperations.php';


$db = new DBconnection();
$con = $db->connection();

if ($con->connect_error) {
    die(json_encode(['error' => true, 'message' => 'Database connection failed', 'error_details' => $con->connect_error]));
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
        echo json_encode($data);
    } else {
        echo json_encode(['error' => true, 'message' => 'Query execution failed', 'error_details' => $con->error]);
    }
}

// Close connection
$con->close();
?>
