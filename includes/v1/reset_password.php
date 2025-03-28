<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../DBconnection.php';

function resetPassword($email, $newPassword) {
    if (!$email || !$newPassword) {
        http_response_code(400);
        echo json_encode(["error" => "Missing email or new password"]);
        exit;
    }

    $db = new DBconnection();
    $conn = $db->connection();

    if (!$conn) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Email not found"]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    if (!$hashedPassword) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to hash password"]);
        $conn->close();
        exit;
    }

    // Update password
    $updateStmt = $conn->prepare("UPDATE users SET Password = ? WHERE Email = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $email);

    if ($updateStmt->execute()) {
        http_response_code(200);
        echo json_encode(["success" => "Password has been reset successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to reset password", "details" => $updateStmt->error]);
    }

    $updateStmt->close();
    $conn->close();
    exit;
}

// Handle incoming request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    resetPassword(
        $data["email"] ?? null,
        $data["newPassword"] ?? null
    );
} else {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method"]);
    exit;
}
