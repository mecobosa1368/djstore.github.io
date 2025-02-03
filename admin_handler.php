<?php
session_start();
header('Content-Type: application/json');


function checkAdminSession() {
    foreach($_SESSION as $key => $value) {
        if(strpos($key, 'admin_') === 0 && isset($value['role']) && $value['role'] === 'admin') {
            return true;
        }
    }
    return false;
}

if (!checkAdminSession()) {
    header('Location: nacstr.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Грешка във връзката с базата данни']);
    exit();
}


$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
}


error_log("Received action: " . $action);
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

switch($action) {
    case 'get_statistics':
        $stats = [
            'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
            'total_cart_items' => $conn->query("SELECT COUNT(*) as count FROM cart")->fetch_assoc()['count'],
            'total_messages' => $conn->query("SELECT COUNT(*) as count FROM contactmessages")->fetch_assoc()['count']
        ];
        echo json_encode(['success' => true, 'data' => $stats]);
        break;

    case 'get_user':
        $user_id = (int)$_GET['user_id'];
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Потребителят не е намерен']);
        }
        break;

    case 'update_user':
        // Add error logging
        error_log("Update user request received: " . print_r($_POST, true));
        
        // Validate required fields
        if (!isset($_POST['user_id'], $_POST['username'], $_POST['email'], $_POST['role'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }

        $user_id = (int)$_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        
        if (!in_array($role, ['user', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            break;
        }
        
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            break;
        }
        
        $stmt->bind_param("sssi", $username, $email, $role, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made']);
            }
        } else {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
        $stmt->close();
        break;

    case 'delete_user':
        $user_id = (int)$_POST['user_id'];
        
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        
        if ($conn->query("DELETE FROM users WHERE id = $user_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Неуспешно изтриване на потребител']);
        }
        break;

    case 'delete_message':
        $message_id = (int)$_POST['message_id'];
        if ($conn->query("DELETE FROM contactmessages WHERE id = $message_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Съобщението не можа да бъде изтрито']);
        }
        break;

    case 'add_product':
        try {
            if (!isset($_POST['name'], $_POST['price'], $_POST['description'], $_POST['category'])) {
                throw new Exception('Missing required fields: ' . print_r($_POST, true));
            }

            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $description = $conn->real_escape_string($_POST['description']);
            $category = $conn->real_escape_string($_POST['category']);
            
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                } else {
                    throw new Exception('Failed to upload image: ' . error_get_last()['message']);
                }
            }

            $stmt = $conn->prepare("INSERT INTO products (name, price, description, category, image_path) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $stmt->bind_param("sdsss", $name, $price, $description, $category, $image_path);
            
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
            
        } catch (Exception $e) {
            error_log("Error in add_product: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'edit_product':
        try {
            if (!isset($_POST['product_id'], $_POST['name'], $_POST['price'], $_POST['description'], $_POST['category'])) {
                throw new Exception('Missing required fields');
            }

            $product_id = (int)$_POST['product_id'];
            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $description = $conn->real_escape_string($_POST['description']);
            $category = $conn->real_escape_string($_POST['category']);
            
            
            $sql = "UPDATE products SET name = ?, price = ?, description = ?, category = ?";
            $types = "sdss"; 
            $params = array($name, $price, $description, $category);
            
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/";
                $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $sql .= ", image_path = ?";
                    $types .= "s";
                    $params[] = $target_file;
                    
                    
                    $old_image = $conn->query("SELECT image_path FROM products WHERE id = $product_id")->fetch_assoc();
                    if ($old_image && file_exists($old_image['image_path'])) {
                        unlink($old_image['image_path']);
                    }
                }
            }
            
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $product_id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Продуктът е актуализиран успешно',
                    'product_id' => $product_id
                ]);
                
                
                if (file_exists('product_cache.json')) {
                    unlink('product_cache.json');
                }
            } else {
                throw new Exception('Database error: ' . $conn->error);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_order_details':
        $order_id = (int)$_GET['order_id'];
        
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($order = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
        }
        break;

    case 'get_product':
        try {
            $product_id = (int)$_GET['product_id'];
            
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Failed to prepare statement');
            }
            
            $stmt->bind_param("i", $product_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute query');
            }
            
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            } else {
                throw new Exception('Продуктът не е намерен');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        error_log("Invalid action received: " . $action);
        echo json_encode(['success' => false, 'message' => 'невалидна сделка: ' . $action]);
        break;
}

$conn->close();
?>
