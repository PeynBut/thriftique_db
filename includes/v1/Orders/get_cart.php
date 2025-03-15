<?php
include '../../DBconnection.php';

$user_id = $_GET['user_id'];

$sql = "SELECT cart_items.id, products.name, products.price, cart_items.quantity
        FROM cart_items
        JOIN products ON cart_items.product_id = products.id
        WHERE cart_items.user_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
while ($row = $result->fetch_assoc()) {
    $cart[] = $row;
}

echo json_encode($cart);
?>
