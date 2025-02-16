<?php
require_once '../../DBoperations.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['action'])) {
        $db = new DBoperations();

        switch ($data['action']) {
            case 'create':
                if (isset($data['user_id']) && isset($data['product_id']) && isset($data['quantity']) && isset($data['total_price']) && isset($data['phone'])) {
                    $result = $db->createOrder($data['user_id'], $data['product_id'], $data['quantity'], $data['total_price']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Order created successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to create order";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'update':
                if (isset($data['id']) && isset($data['status'])) {
                    $result = $db->updateOrder($data['id'], $data['status']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Order updated successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to update order";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'delete':
                if (isset($data['id'])) {
                    $result = $db->deleteOrder($data['id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Order deleted successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to delete order";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'track':
                if (isset($data['phone']) && isset($data['tracking_number'])) {
                    $order = $db->trackOrder($data['phone'], $data['tracking_number']);
                    if ($order) {
                        $response['error'] = false;
                        $response['order'] = $order;
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Order not found";
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
    $db = new DBoperations();
    if (isset($_GET['id'])) {
        $order = $db->getOrderById($_GET['id']);
        if ($order) {
            $response['error'] = false;
            $response['order'] = $order;
        } else {
            $response['error'] = true;
            $response['message'] = "Order not found";
        }
    } elseif (isset($_GET['phone'])) {
        $orders = $db->getOrderHistory($_GET['phone']);
        $response['error'] = false;
        $response['orders'] = array();
        while ($order = $orders->fetch_assoc()) {
            array_push($response['orders'], $order);
        }
    } else {
        $orders = $db->getOrders();
        $response['error'] = false;
        $response['orders'] = array();
        while ($order = $orders->fetch_assoc()) {
            array_push($response['orders'], $order);
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>