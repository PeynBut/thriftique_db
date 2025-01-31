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
                if (isset($data['user_id']) && isset($data['product_id'])) {
                    $result = $db->addToWishlist($data['user_id'], $data['product_id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product added to wishlist successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to add product to wishlist";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'remove':
                if (isset($data['user_id']) && isset($data['product_id'])) {
                    $result = $db->removeFromWishlist($data['user_id'], $data['product_id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product removed from wishlist successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to remove product from wishlist";
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
        $wishlist = $db->getWishlist($_GET['user_id']);
        if ($wishlist) {
            $response['error'] = false;
            $response['wishlist'] = array();
            while ($item = $wishlist->fetch_assoc()) {
                array_push($response['wishlist'], $item);
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Wishlist not found";
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