<?php
// filepath: /c:/xampp2/htdocs/android/includes/v1/Register.php
require_once '../DBoperations.php';
header("Content-Type: application/json");

$rawPostData = file_get_contents("php://input");
$data = json_decode($rawPostData, true);
error_log("Received data: " . print_r($data, true)); // Log received data


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Invalid request method']);
    exit;
}

// Check required fields
$requiredFields = ['firstName', 'lastName', 'email', 'password', 'confirmPassword'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['error' => true, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => true, 'message' => 'Invalid email format']);
    exit;
}

// Check if passwords match
if ($data['password'] !== $data['confirmPassword']) {
    echo json_encode(['error' => true, 'message' => 'Passwords do not match']);
    exit;
}

// Secure password hashing
$hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

// Generate a secure token
$token = bin2hex(random_bytes(32)); // Generates a 64-character unique token

try {
    $db = new DBoperations();

    // Check if email already exists
    if ($db->checkUserExists($data['email'])) {
        echo json_encode(['error' => true, 'message' => 'Email already registered']);
        exit;
    }

    // Register user with token
    $result = $db->registerUser($data['firstName'], $data['lastName'], $data['email'], $hashedPassword, $token);
    
    if ($result) {
        echo json_encode([
            'error' => false, 
            'message' => 'Registration successful',
            'token' => $token // Return the token
        ]);
    } else {
        echo json_encode(['error' => true, 'message' => 'Registration failed']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
