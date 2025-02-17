<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../DBoperations.php';
$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the form is submitted with multipart/form-data (for file uploads)
    if (isset($_FILES['image']) && isset($_POST['name']) && isset($_POST['description']) && isset($_POST['price'])) {
        // Retrieve form data
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $image = $_FILES['image'];

        // Handle image upload
        $imageName = time() . '-' . basename($image['name']);
        $uploadDir = 'uploads/products/';
        $uploadPath = $uploadDir . $imageName;

        // Check if the image was successfully uploaded
        if (move_uploaded_file($image['tmp_name'], $uploadPath)) {
            $db = new DBoperations();

            // Create the product with the image path
            $result = $db->createProductWithImage($name, $description, $price, $uploadPath);
            if ($result == 1) {
                $response['error'] = false;
                $response['message'] = "Product created successfully";
            } else {
                $response['error'] = true;
                $response['message'] = "Failed to create product";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Failed to upload image";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Required fields are missing";
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
