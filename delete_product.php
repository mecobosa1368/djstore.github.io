<?php
header('Content-Type: application/json');
session_start();

// Admin session kontrolü fonksiyonu
function checkAdminSession() {
    foreach($_SESSION as $key => $value) {
        if(strpos($key, 'admin_') === 0 && isset($value['role']) && $value['role'] === 'admin') {
            return true;
        }
    }
    return false;
}

// Admin kontrolü
if (!checkAdminSession()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    
    $result = $conn->query("SELECT image_path FROM products WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $image_path = $row['image_path'];
    }
    
    if ($conn->query("DELETE FROM products WHERE id = $id")) {
        
        if (isset($image_path) && file_exists($image_path) && !strpos($image_path, 'default')) {
            unlink($image_path);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Изтриването не бе успешно']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Не е предоставен документ за самоличност']);
}

$conn->close(); 