<?php
session_start();

require_once "../../DBconnection.php"; // Make sure this path is correct

// Create a new DBconnection instance and establish the connection
$db = new DBconnection();
$con = $db->connection(); // Get the database connection

// Check if connection failed
if (!$con) {
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Fetch notifications
$sql = "SELECT id, message FROM notifications ORDER BY created_at DESC LIMIT 10";
$result = $con->query($sql);

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$con->close();

// Return JSON response
header("Content-Type: application/json");
echo json_encode($notifications);
?>
