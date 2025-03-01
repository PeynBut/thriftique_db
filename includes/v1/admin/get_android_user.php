<?php
require_once "../../DBoperations.php"; // Ensure this path is correct

header("Content-Type: application/json");

$response = array();

$db = new DBoperations();

// Ensure `getUserCount` function exists in DBoperations.php
$userCount = $db->getUserCount();

if ($userCount !== false) {
    $response['error'] = false;
    $response['user_count'] = $userCount;
} else {
    $response['error'] = true;
    $response['message'] = "Failed to fetch user count";
}

echo json_encode($response);
?>
