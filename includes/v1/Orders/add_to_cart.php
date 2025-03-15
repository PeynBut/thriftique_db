<?php
require_once '../../DBconnection.php'; // ✅ Adjust path based on your project structure

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_POST['user_id'];
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    if (!isset($con)) {
        die(json_encode(["success" => false, "message" => "Database connection error!"]));
    }

    $stmt = $con->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $userId, $productId, $quantity);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Item added to cart successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add item to cart!"]);
    }

    $stmt->close();
    $con->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method!"]);
}
?>
