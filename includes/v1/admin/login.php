<?php
session_start();

// Enable error reporting for debugging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log'); // Ensure this path exists or change to another one
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log raw input and method details for debugging
file_put_contents("php://stderr", "Request received!\n", FILE_APPEND);
error_log("Received request method: " . $_SERVER['REQUEST_METHOD']);

// Enable CORS headers to allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request (for OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => true, "message" => "Invalid request method. Expected POST."]);
    exit();
}

// Get the raw POST data (JSON)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// If decoding JSON fails
if (!$data) {
    echo json_encode(["error" => true, "message" => "Invalid JSON format."]);
    exit();
}

// Validate required fields (email and password)
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["error" => true, "message" => "Email and Password are required."]);
    exit();
}

// Sanitize email and password
$email = trim($data['email']);
$password = trim($data['password']);

// Database credentials
$host = "localhost";
$dbname = "thriftique_db";
$username = "root";
$passwordDB = ""; // Use a different variable name for the DB password

// Try to connect to the database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $passwordDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Check if the user exists in the database
$stmt = $pdo->prepare("SELECT id, first_name, last_name, password FROM admins WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If no user found
if (!$user) {
    echo json_encode(["error" => true, "message" => "Invalid email or password."]);
    exit();
}

// Verify the password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["error" => true, "message" => "Invalid email or password."]);
    exit();
}

// Set session variables if login is successful
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_name'] = $user['first_name'];

// Return success message with admin name
echo json_encode([
    "error" => false,
    "message" => "Login successful!",
    "admin_name" => $user['first_name']
]);
?>