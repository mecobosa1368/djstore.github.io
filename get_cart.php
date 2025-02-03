<?php
session_start();
header('Content-Type: application/json');

function getUserSession() {
    foreach($_SESSION as $key => $value) {
        if(strpos($key, 'user_') === 0) {
            return $value;
        }
    }
    return null;
}

$userSession = getUserSession();
if (!$userSession) {
    echo json_encode(['success' => false, 'message' => 'Моля първо влезте.']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Свързването на база данни е неуспешно: ' . $conn->connect_error]);
    exit();
}

$user_id = $userSession['user_id'];
$cart_query = "SELECT product_name, quantity, price FROM cart WHERE user_id = $user_id";
$cart_result = $conn->query($cart_query);

$cart_items = [];
if ($cart_result->num_rows > 0) {
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
    }
}

echo json_encode(['success' => true, 'cart_items' => $cart_items]);

$conn->close();
?>
