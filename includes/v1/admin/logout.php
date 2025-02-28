<?php
session_start();
session_destroy(); // Destroy the session
setcookie(session_name(), '', time() - 3600, '/'); // Clear session cookie
header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Logout successful"]);
exit;
?>
