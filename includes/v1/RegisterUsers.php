<?php
require_once '../DBoperations.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log("Received data: " . print_r($data, true)); // Add logging

    if (isset($data['firstName']) && isset($data['lastName']) && isset($data['email']) && isset($data['password'])) {
        $db = new DBoperations();
        $result = $db->createUser(
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['password']
        );
        if ($result['status'] == 1) {
            $response['error'] = false;
            $response['message'] = "User registered successfully";
            $response['token'] = $result['token'];
            error_log("User registered successfully: " . print_r($data, true)); // Add logging
        } elseif ($result['status'] == 2) {
            $response['error'] = true;
            $response['message'] = "Some error occurred, please try again";
            error_log("Error occurred while registering user: " . print_r($data, true)); // Add logging
        } elseif ($result['status'] == 0) {
            $response['error'] = true;
            $response['message'] = "It seems you are already registered, please choose a different email and username";
            error_log("User already registered: " . print_r($data, true)); // Add logging
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Required fields are missing";
        error_log("Required fields are missing: " . print_r($data, true)); // Add logging
    }
    echo json_encode($response);
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
    error_log("Invalid request method"); // Add logging
    echo json_encode($response);
}
?>