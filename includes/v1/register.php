<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection class
include_once '../DBconnection.php';

function registerUser($first_name, $last_name, $email, $password_raw, $confirm_pass) {
    $db = new DBconnection();
    $conn = $db->connection();
    if (!$conn) {
        return json_encode(["error" => "Database connection failed"]);
    }
    
    // Validate inputs
    if (!$first_name || !$last_name || !$email || !$password_raw || !$confirm_pass) {
        return json_encode(["error" => "Missing required fields"]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return json_encode(["error" => "Invalid email format"]);
    }

    if ($password_raw !== $confirm_pass) {
        return json_encode(["error" => "Passwords do not match"]);
    }

    // Hash the password
    $password = password_hash($password_raw, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32));

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE Email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        return json_encode(["error" => "Email already registered"]);
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (FirstName, Lastname, Email, Password, token) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $token);

    if ($stmt->execute()) {
        return json_encode(["message" => "Registration successful", "token" => $token]);
    } else {
        return json_encode(["error" => "Failed to register user", "detail" => $stmt->error]);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    echo registerUser(
        $data["first_name"] ?? $data["firstName"] ?? null,
        $data["last_name"] ?? $data["lastName"] ?? null,
        $data["email"] ?? null,
        $data["password"] ?? null,
        $data["confirm_password"] ?? $data["confirmPassword"] ?? null
    );
} else {
    echo json_encode(["error" => "Invalid request method"]);
}
?>