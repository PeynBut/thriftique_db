<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Origin, Authorization");

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle preflight requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Read JSON input
$input = file_get_contents("php://input");
error_log("Raw input data: " . $input); // Log raw input data for debugging

if (empty($input)) {
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "Request body is empty."]);
    exit();
}

// Decode JSON
$data = json_decode($input, true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decoding error: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "Invalid JSON data."]);
    exit();
}

// Validate required fields
if (
    !isset($data['firstName']) || !isset($data['lastName']) ||
    !isset($data['email']) || !isset($data['password']) ||
    empty($data['firstName']) || empty($data['lastName']) ||
    empty($data['email']) || empty($data['password'])
) {
    http_response_code(400);
    echo json_encode(["error" => true, "message" => "All fields are required."]);
    exit();
}

// Extract form data
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$email = trim($data['email']);
$password = password_hash(trim($data['password']), PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32)); // Generate a secure token

// Database connection
$host = 'localhost';
$db = 'thriftique'; // Replace with your database name
$user = 'root'; // Replace with your database username
$pass = ''; // Replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert data into the database with token
    $stmt = $pdo->prepare("INSERT INTO admins (first_name, last_name, email, password, token) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$firstName, $lastName, $email, $password, $token]);

    http_response_code(200);
    echo json_encode(["error" => false, "message" => "Registration successful!", "token" => $token]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage()); // Log the database error
    http_response_code(500);
    echo json_encode(["error" => true, "message" => "Database error: " . $e->getMessage()]);
}
?>
