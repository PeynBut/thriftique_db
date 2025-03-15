<?php
include '../../DBconnection.php';

$cart_id = $_POST['cart_id'];

$sql = "DELETE FROM cart_items WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cart_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Item removed"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to remove item"]);
}
?>
