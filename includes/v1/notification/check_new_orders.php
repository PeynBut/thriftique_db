<?php
session_start();

require_once "../../DBconnection.php"; // Ensure the path is correct

// Create a new DBconnection instance and establish the connection
$db = new DBconnection();
$con = $db->connection(); // Get the database connection

// Check if connection failed
if (!$con) {
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Get the last processed order ID from the order_notifications table
$lastOrderResult = $con->query("SELECT last_order_id FROM order_notifications ORDER BY updated_at DESC LIMIT 1");
$lastOrderId = 0; // Default to 0 if no entry is found
if ($lastOrderResult && $lastOrderResult->num_rows > 0) {
    $row = $lastOrderResult->fetch_assoc();
    $lastOrderId = $row['last_order_id'];
}

// Debugging: log lastOrderId
error_log("Last Order ID: " . $lastOrderId);

// Query to fetch all users who have placed orders and order details (including those whose order_id is greater than lastOrderId)
$sql = "SELECT o.id as order_id, CONCAT(u.FirstName, ' ', u.Lastname) as full_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE TRIM(o.status) = 'Placed' 
        ORDER BY o.created_at DESC";


error_log("SQL Query: " . $sql); // Log the SQL query for debugging

$result = $con->query($sql);

$new_orders = []; // Initialize the array to store orders

if ($result && $result->num_rows > 0) {
    // Fetch the new orders
    while ($row = $result->fetch_assoc()) {
        $new_orders[] = $row;

        // Mark the order as notified (update 'notified' field to 1)
        $updateSql = "UPDATE orders SET notified = 1 WHERE id = " . $row['order_id'];
        
        // Check if the UPDATE query was successful
        if (!$con->query($updateSql)) {
            // Log any errors
            error_log("Error updating notified status for order ID " . $row['order_id'] . ": " . $con->error);
        }
    }

    // After processing new orders, update the last processed order ID in the order_notifications table
    $latestOrderId = $new_orders[0]['order_id']; // The first order in the result will have the highest ID

    // Insert the new last order ID
    $updateLastOrderId = "INSERT INTO order_notifications (last_order_id) VALUES ($latestOrderId)";
    $con->query($updateLastOrderId);
} else {
    $new_orders = []; // No new orders found
}

// Close the database connection
$con->close();

// Set the response header to JSON
header("Content-Type: application/json");

// Return the new orders as a JSON response
echo json_encode($new_orders);
?>
