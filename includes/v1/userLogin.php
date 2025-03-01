<?php
require_once '../DBoperations.php';
$response = array();
$_SESSION['user_id'] = $user['id']; // Store Android user ID in session

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['email']) && isset($data['password'])) {
        $db = new DBoperations();
        if ($db->userLogin($data['email'], $data['password'])) {
            $user = $db->getUserByEmail($data['email']);
            $token = bin2hex(random_bytes(16)); // Generate a random token
            $db->storeToken($user['id'], $token); // Store the token in the database
            $response['error'] = false;
            $response['id'] = $user['id'];
            $response['firstName'] = $user['firstName'];
            $response['lastName'] = $user['lastName'];
            $response['email'] = $user['email'];
            $response['token'] = $token; // Return the token
        } else {
            $response['error'] = true;
            $response['message'] = "Invalid email or password";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Required fields are missing";
    }
    echo json_encode($response);
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
    echo json_encode($response);
}
?>