<?php
// filepath: /c:/xampp2/htdocs/android/includes/v1/RegisterPart1.php
require_once '../DBoperations.php';
header("Content-Type: application/json");

$rawPostData = file_get_contents("php://input");
$data = json_decode($rawPostData, true);

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

// Store the first part of the registration data in a session
session_start();
$_SESSION['registration'] = [
    'firstName' => $data['firstName'],
    'lastName' => $data['lastName'],
    'email' => $data['email'],
    'password' => $hashedPassword
];

echo json_encode(['error' => false, 'message' => 'First part of registration completed']);
?>