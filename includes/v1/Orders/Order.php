<?php
require_once '../../DBoperations.php';
$response = array();

// Ensure the response is always JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['action'])) {
        $response['error'] = true;
        $response['message'] = "Action not specified";
        echo json_encode($response);
        exit;
    }

    $db = new DBoperations();

    switch ($data['action']) {
        case 'create':
            if (isset($data['user_id'], $data['product_id'], $data['quantity'], $data['total_price'])) {
                $result = $db->createOrder($data['user_id'], $data['product_id'], $data['quantity'], $data['total_price']);
                $response['error'] = $result !== 1;
                $response['message'] = $result === 1 ? "Order created successfully" : "Failed to create order";
            } else {
                $response['error'] = true;
                $response['message'] = "Required fields are missing";
            }
            break;

        case 'update':
            if (isset($data['id'], $data['status'])) {
                $validStatuses = ['Placed', 'Preparing', 'Ready', 'Completed']; // Allowed statuses
                if (!in_array($data['status'], $validStatuses)) {
                    $response['error'] = true;
                    $response['message'] = "Invalid status provided";
                } else {
                    $result = $db->updateOrderStatus($data['id'], $data['status']);
                    $response['error'] = $result !== 1;
                    $response['message'] = $result === 1 ? "Order status updated successfully" : "Failed to update order status";
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Required fields are missing";
            }
            break;

        case 'delete':
            if (isset($data['id'])) {
                $result = $db->deleteOrder($data['id']);
                $response['error'] = $result !== 1;
                $response['message'] = $result === 1 ? "Order deleted successfully" : "Failed to delete order";
            } else {
                $response['error'] = true;
                $response['message'] = "Required fields are missing";
            }
            break;

        case 'track':
            if (isset($data['phone'], $data['tracking_number'])) {
                $order = $db->trackOrder($data['phone'], $data['tracking_number']);
                if ($order) {
                    $order['total_price'] = "₱" . number_format($order['total_price'], 2);
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
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $db = new DBoperations();
    $con = $db->connection();

    if (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id']; // No need for intval()
        $sql = "SELECT orders.id, orders.status, orders.total_price, 
                       CONCAT(users.FirstName, ' ', users.Lastname) AS user_name, 
                       products.name AS product_name, orders.quantity, 
                       products.image 
                FROM orders
                JOIN users ON orders.user_id = users.id
                JOIN products ON orders.product_id = products.id
                WHERE orders.user_id = ? 
                ORDER BY orders.id DESC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $user_id); // Ensures it's an integer
    }    
    else {
        // Fetch all orders for Admin
        $sql = "SELECT orders.id, orders.status, orders.total_price, 
                       CONCAT(users.FirstName, ' ', users.Lastname) AS user_name, 
                       products.name AS product_name, orders.quantity, 
                       products.image 
                FROM orders
                JOIN users ON orders.user_id = users.id
                JOIN products ON orders.product_id = products.id
                ORDER BY orders.id DESC";
        $stmt = $con->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $response['orders'] = [];
    $base_url = "http://192.168.100.184/thriftique_db/includes/v1/Products/uploads/";

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['image'])) {
            // ✅ Ensure image URLs are correctly formatted
            if (!str_starts_with($row['image'], "http")) {
                $row['image'] = $base_url . basename($row['image']); // Fix missing full URL
            }
        } else {
            $row['image'] = $base_url . "default.jpg"; // Placeholder for missing images
        }

        $row['total_price'] = "₱" . number_format(floatval($row['total_price']), 2);
        $response['orders'][] = $row;
    }

    echo json_encode($response);
    exit;
    } else {
        $response['error'] = true;
        $response['message'] = "Invalid request method";
    }


echo json_encode($response);
exit;
?>