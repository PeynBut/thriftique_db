<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../DBconnection.php';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_GET["email"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Email is required"]);
        exit;
    }

    $email = $_GET["email"];

    $db = new DBconnection();
    $conn = $db->connection();

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Database connection failed"]);
        exit;
    }

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Email exists
        http_response_code(200);
        echo json_encode(["success" => true, "message" => "Email exists, proceed to reset password"]);
    } else {
        // Email not found
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Email not registered"]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
}
?>
