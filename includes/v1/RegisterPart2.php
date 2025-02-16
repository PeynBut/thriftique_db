<?php
require_once '../DBoperations.php';
header("Content-Type: application/json");
session_start(); // Ensure session starts before any output

$rawPostData = file_get_contents("php://input");
$data = json_decode($rawPostData, true);

// Debug: Log the received JSON data
error_log("Received Data: " . print_r($data, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => true, 'message' => 'Invalid request method']);
    exit;
}

// Check required fields
$requiredFields = ['phone', 'barangay', 'municipality', 'country', 'province', 'postalCode'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['error' => true, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Validate phone number (11 digits)
if (!preg_match("/^[0-9]{11}$/", $data['phone'])) {
    echo json_encode(['error' => true, 'message' => 'Invalid phone number']);
    exit;
}

// Construct the full address
$placeName = $data['barangay'] . ', ' . $data['municipality'] . ', ' . $data['province'] . ', ' . $data['country'] . ', ' . $data['postalCode'];

// Debug: Log session data before using it
error_log("Session Data: " . print_r($_SESSION['registration'] ?? "No session data", true));

if (!isset($_SESSION['registration'])) {
    echo json_encode(['error' => true, 'message' => 'First part of registration not completed']);
    exit;
}

$registrationData = $_SESSION['registration'];

// Debug: Log final user data before inserting into DB
error_log("Final User Data: " . print_r($registrationData, true));

$db = new DBoperations();

// Generate a token (for example, using JWT or a simple random string)
$token = bin2hex(random_bytes(16)); // Example token generation

$result = $db->registerUser([
    'firstName' => $registrationData['firstName'],
    'lastName' => $registrationData['lastName'],
    'email' => $registrationData['email'],
    'password' => password_hash($registrationData['password'], PASSWORD_BCRYPT),
    'phoneNumber' => $data['phone'],
    'address' => $placeName,
    'token' => $token
]);


// Clear the session data
unset($_SESSION['registration']);

// Response handling
$response = [];
if ($result) {
    $response = ['error' => false, 'message' => "User registered successfully", 'token' => $token];
} else {
    $response = ['error' => true, 'message' => "An error occurred, please try again"];
}
echo json_encode($response);
?>