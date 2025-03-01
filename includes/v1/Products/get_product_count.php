<?php
require_once "../../DBoperations.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$response = array();
$db = new DBoperations();

// Query to count total products
$query = "SELECT COUNT(*) as total FROM products";
$result = $db->executeQuery($query);

if ($result) {
    $row = $result->fetch_assoc();
    $response['error'] = false;
    $response['product_count'] = $row['total'];
} else {
    $response['error'] = true;
    $response['message'] = "Failed to fetch product count";
}

echo json_encode($response);
?>
