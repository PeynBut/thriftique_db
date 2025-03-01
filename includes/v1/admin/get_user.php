<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["error" => true, "message" => "Not logged in"]);
    exit();
}

echo json_encode([
    "error" => false,
    "first_name" => $_SESSION['admin_name'],
    "last_name" => $_SESSION['last_name'] // Add last name here
]);
exit();
?>
