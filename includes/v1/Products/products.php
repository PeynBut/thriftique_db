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
                if (isset($data['name']) && isset($data['description']) && isset($data['price'])) {
                    $result = $db->createProduct($data['name'], $data['description'], $data['price']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product created successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to create product";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'update':
                if (isset($data['id']) && isset($data['name']) && isset($data['description']) && isset($data['price'])) {
                    $result = $db->updateProduct($data['id'], $data['name'], $data['description'], $data['price']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product updated successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to update product";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'delete':
                if (isset($data['id'])) {
                    $result = $db->deleteProduct($data['id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Product deleted successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to delete product";
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
        $product = $db->getProductById($_GET['id']);
        if ($product) {
            $response['error'] = false;
            $response['product'] = $product;
        } else {
            $response['error'] = true;
            $response['message'] = "Product not found";
        }
    } else {
        $db = new DBoperations();
        $products = $db->getProducts();
        $response['error'] = false;
        $response['products'] = array();
        while ($product = $products->fetch_assoc()) {
            array_push($response['products'], $product);
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>