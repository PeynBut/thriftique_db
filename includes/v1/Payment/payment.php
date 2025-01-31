<?php
require_once '../../DBoperations.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['action'])) {
        $db = new DBoperations();

        switch ($data['action']) {
            case 'process':
                if (isset($data['order_id']) && isset($data['user_id']) && isset($data['amount'])) {
                    $result = $db->processPayment($data['order_id'], $data['user_id'], $data['amount']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Payment processed successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to process payment";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            default:
                $response['error'] = true;
                $response['message'] = "Invalid action";
                break;
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Action not specified";
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $db = new DBoperations();
        $payment = $db->getPaymentStatus($_GET['id']);
        if ($payment) {
            $response['error'] = false;
            $response['payment'] = $payment;
        } else {
            $response['error'] = true;
            $response['message'] = "Payment not found";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Payment ID not specified";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>