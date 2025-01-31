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
                if (isset($data['product_id']) && isset($data['user_id']) && isset($data['rating']) && isset($data['review'])) {
                    $result = $db->addReview($data['product_id'], $data['user_id'], $data['rating'], $data['review']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Review added successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to add review";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'update':
                if (isset($data['id']) && isset($data['rating']) && isset($data['review'])) {
                    $result = $db->updateReview($data['id'], $data['rating'], $data['review']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Review updated successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to update review";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'delete':
                if (isset($data['id'])) {
                    $result = $db->deleteReview($data['id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Review deleted successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to delete review";
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
    if (isset($_GET['product_id'])) {
        $db = new DBoperations();
        $reviews = $db->getReviews($_GET['product_id']);
        if ($reviews) {
            $response['error'] = false;
            $response['reviews'] = array();
            while ($review = $reviews->fetch_assoc()) {
                array_push($response['reviews'], $review);
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Reviews not found";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "Product ID not specified";
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>