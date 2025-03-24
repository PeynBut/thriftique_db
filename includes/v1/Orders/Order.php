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
    $con = $db->connection(); // Get the connection from DBoperations

    if (isset($_GET['id'])) {
        // Fetch order by ID using your DBoperations method
        $order = $db->getOrderById($_GET['id']);
        if ($order) {
            $order['total_price'] = "₱" . number_format(floatval($order['total_price']), 2);
            // Build the user name from available fields
            $firstName = isset($order['FirstName']) ? $order['FirstName'] : "Unknown";
            $lastName = isset($order['Lastname']) ? $order['Lastname'] : "User";
            $order['user_name'] = $firstName . " " . $lastName;
            // Optionally remove individual name parts if not needed
            unset($order['user_id'], $order['FirstName'], $order['Lastname']);
            $response['error'] = false;
            $response['order'] = $order;
        } else {
            $response['error'] = true;
            $response['message'] = "Order not found or invalid ID";
        }
    } else {
        // Fetch all orders with concatenated user name
        $sql = "SELECT orders.id, orders.status, orders.total_price, 
                       CONCAT(users.FirstName, ' ', users.Lastname) AS user_name, 
                       products.name AS product_name, orders.quantity, 
                       products.image 
                FROM orders
                JOIN users ON orders.user_id = users.id
                JOIN products ON orders.product_id = products.id
                ORDER BY orders.id DESC";

        $result = $con->query($sql);
        $response['error'] = false;
        $response['orders'] = [];

        while ($row = $result->fetch_assoc()) {
            // Ensure image URL is properly formatted
            if (!empty($row['image'])) {
                if (!preg_match('/^http/', $row['image'])) {
                    $row['image'] = "http://192.168.100.184/thriftique_db/includes/v1/Products/uploads/" . $row['image'];
                }
            } else {
                $row['image'] = "http://192.168.100.184/thriftique_db/includes/v1/Products/uploads/67b74195ca83d.jpg";
            }

            // Format total_price display
            $row['total_price'] = "₱" . number_format(floatval($row['total_price']), 2);
            $response['orders'][] = $row;
        }
    }

    $con->close();
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request";
}

echo json_encode($response);
exit;
?>