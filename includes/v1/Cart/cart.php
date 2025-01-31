<?php
require_once '../../DBoperations.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['action'])) {
        $db = new DBoperations();

        switch ($data['action']) {
            case 'add':
                if (isset($data['user_id']) && isset($data['product_id']) && isset($data['quantity'])) {
                    $result = $db->addToCart($data['user_id'], $data['product_id'], $data['quantity']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product added to cart successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to add product to cart";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'update':
                if (isset($data['user_id']) && isset($data['product_id']) && isset($data['quantity'])) {
                    $result = $db->updateCart($data['user_id'], $data['product_id'], $data['quantity']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Cart updated successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to update cart";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'clear':
                if (isset($data['user_id'])) {
                    $result = $db->clearCart($data['user_id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Cart cleared successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to clear cart";
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
    if (isset($_GET['user_id'])) {
        $db = new DBoperations();
        $cart = $db->getCart($_GET['user_id']);
        if ($cart) {
            $response['error'] = false;
            $response['cart'] = array();
            while ($item = $cart->fetch_assoc()) {
                array_push($response['cart'], $item);
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Cart not found";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "User ID not specified";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>