<?php
require_once '../../DBoperations.php';
header("Content-Type: application/json");

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['phone'])) {
        $db = new DBoperations();
        $otp = $db->generateOTP($data['phone']);
        if ($otp) {
            if ($db->sendOTP($data['phone'], $otp)) {
                $response['error'] = false;
                $response['message'] = "OTP sent successfully";
                $response['otp'] = $otp; // Add OTP to the response
            } else {
                $response['error'] = true;
                $response['message'] = "Failed to send OTP";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Failed to generate OTP";
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