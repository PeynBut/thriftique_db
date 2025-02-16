<?php
session_start();

// Database credentials
$host = "localhost";
$dbname = "thriftique"; // Your database name
$username = "root"; // Change if needed
$password = ""; // Change if you have a MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => true, "message" => "Database connection failed: " . $e->getMessage()]));
}

// Read JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Validate input
if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(["error" => true, "message" => "Email and Password are required."]);
    exit();
}

$email = trim($data['email']);
$password = trim($data['password']);

// Check if the user exists
$stmt = $pdo->prepare("SELECT id, first_name, last_name, password FROM admins WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['admin_id'] = $user['id']; // Store user session
    echo json_encode(["error" => false, "message" => "Login successful!", "admin_name" => $user['first_name']]);
} else {
    echo json_encode(["error" => true, "message" => "Invalid email or password."]);
}
?>
