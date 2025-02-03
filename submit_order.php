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

try {
    // Sipariş bilgilerini al
    $name = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $cart_items = $_POST['cart_items'];
    $total_price = floatval($_POST['total_price']);
    $user_id = $userSession['user_id'];
    
    // Siparişi veritabanına ekle
    $sql = "INSERT INTO orders (user_id, name, address, phone, email, cart_items, total_price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssd", 
        $user_id, 
        $name, 
        $address, 
        $phone, 
        $email, 
        $cart_items,
        $total_price
    );
    
    if ($stmt->execute()) {
        // Sepeti temizle
        $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart->bind_param("i", $user_id);
        $clear_cart->execute();
        
        echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
    } else {
        throw new Exception('Order could not be placed');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>