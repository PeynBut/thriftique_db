<?php
require_once '../../DBoperations.php';
header("Content-Type: application/json");

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['phone']) && isset($data['otp']) && isset($data['new_password'])) {
        $db = new DBoperations();
        
        // Debugging: Log the received data
        error_log("Phone: " . $data['phone']);
        error_log("OTP: " . $data['otp']);
        error_log("New Password: " . $data['new_password']);
        
        if ($db->verifyOTP($data['phone'], $data['otp'])) {
            $hashedPassword = md5($data['new_password']);
            $result = $db->updatePassword($data['phone'], $hashedPassword);
            if ($result) {
                $response['error'] = false;
                $response['message'] = "Password reset successfully";
            } else {
                $response['error'] = true;
                $response['message'] = "Failed to reset password";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Invalid or expired OTP";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Required fields are missing";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>