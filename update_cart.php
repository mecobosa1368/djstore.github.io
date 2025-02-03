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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$product_name = $conn->real_escape_string($data['product_name']);
$new_quantity = isset($data['new_quantity']) ? (int)$data['new_quantity'] : 0;

$user_id = $userSession['user_id'];

if ($new_quantity > 0) {
    // Miktarı güncelle
    $query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $new_quantity, $user_id, $product_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Количеството е актуализирано']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Грешка при актуализиране на количеството']);
    }
} else {
    // Ürünü sepetten sil
    $query = "DELETE FROM cart WHERE user_id = ? AND product_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $product_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Продуктът е премахнат от кошницата']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Грешка при премахване на продукта']);
    }
}

$stmt->close();
$conn->close();
?>
