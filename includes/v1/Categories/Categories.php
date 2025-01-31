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
                if (isset($data['name']) && isset($data['description'])) {
                    $result = $db->createCategory($data['name'], $data['description']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Category created successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to create category";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'update':
                if (isset($data['id']) && isset($data['name']) && isset($data['description'])) {
                    $result = $db->updateCategory($data['id'], $data['name'], $data['description']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Category updated successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to update category";
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = "Required fields are missing";
                }
                break;

            case 'delete':
                if (isset($data['id'])) {
                    $result = $db->deleteCategory($data['id']);
                    if ($result == 1) {
                        $response['error'] = false;
                        $response['message'] = "Category deleted successfully";
                    } else {
                        $response['error'] = true;
                        $response['message'] = "Failed to delete category";
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
        $category = $db->getCategoryById($_GET['id']);
        if ($category) {
            $response['error'] = false;
            $response['category'] = $category;
        } else {
            $response['error'] = true;
            $response['message'] = "Category not found";
        }
    } else {
        $db = new DBoperations();
        $categories = $db->getCategories();
        $response['error'] = false;
        $response['categories'] = array();
        while ($category = $categories->fetch_assoc()) {
            array_push($response['categories'], $category);
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>