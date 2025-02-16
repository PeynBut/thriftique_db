<?php
session_start();

file_put_contents("php://stderr", "Request received!\n", FILE_APPEND);

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log'); // Change path if needed
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_log("Login API hit"); // Log if API is accessed

// Database credentials
$host = "localhost";
$dbname = "thriftique";
$username = "root"; 
$password = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Read JSON input
$input = file_get_contents("php://input");

if (!$input) {
    echo json_encode(["error" => true, "message" => "Invalid request. No input received."]);
    exit();
}

error_log("Received JSON: " . $input);
$data = json_decode($input, true);

// Validate input
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["error" => true, "message" => "Email and Password are required."]);
    exit();
}

$email = trim($data['email']);
$password = trim($data['password']);

// Check if the user exists
$stmt = $pdo->prepare("SELECT id, first_name, last_name, password FROM admins WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["error" => true, "message" => "Invalid email or password."]);
    exit();
}

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(["error" => true, "message" => "Invalid email or password."]);
    exit();
}

// Set session variables
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_name'] = $user['first_name'];

echo json_encode([
    "error" => false,
    "message" => "Login successful!",
    "admin_name" => $user['first_name']
]);
?>
