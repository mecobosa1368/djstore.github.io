<?php
// Hata raporlamasını aktif et
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Admin session kontrolü fonksiyonu
function checkAdminSession() {
    foreach($_SESSION as $key => $value) {
        if(strpos($key, 'admin_') === 0 && isset($value['role']) && $value['role'] === 'admin') {
            return $value;
        }
    }
    return false;
}

// Sadece admin kontrolü
$adminSession = checkAdminSession();
if (!$adminSession) {
    header('Location: nacstr.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && $_POST['action'] === 'add_product') {
        try {
            $name = $conn->real_escape_string($_POST['name']);
            $price = floatval($_POST['price']);
            $description = $conn->real_escape_string($_POST['description']);
            $category = $conn->real_escape_string($_POST['category']);
            
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image_path = '';
            if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
                $target_file = $target_dir . time() . '_' . basename($_FILES["image"]["name"]);
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    throw new Exception('Грешка при качване на файл');
                }
            }

            $sql = "INSERT INTO products (name, price, description, category, image_path) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdsss", $name, $price, $description, $category, $image_path);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Продуктът е добавен успешно']);
            } else {
                throw new Exception('грешка в базата данни: ' . $conn->error);
            }
            $stmt->close();
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'total_cart_items' => $conn->query("SELECT COUNT(*) as count FROM cart")->fetch_assoc()['count'],
    'total_messages' => $conn->query("SELECT COUNT(*) as count FROM contactmessages")->fetch_assoc()['count']
];

$recent_messages = $conn->query("SELECT * FROM contactmessages ORDER BY id DESC LIMIT 5");

$recent_users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");

$cart_items = $conn->query("
    SELECT c.*, u.username 
    FROM cart c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.id DESC 
    LIMIT 5
");

$orders = $conn->query("
    SELECT o.*, u.username 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DJ Store Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
    padding: 20px;
    background: #f5f6fa;
}

.admin-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #2c3e50;
    padding: 1rem 2rem;
    color: white;
    border-radius: 8px;
    margin-bottom: 20px;
}

.admin-nav ul {
    display: flex;
    gap: 2rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.admin-nav a {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: background 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.admin-nav a:hover {
    background: #34495e;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card i {
    font-size: 2rem;
    color: #3498db;
    margin-bottom: 1rem;
}

.stat-card h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.1rem;
}

.stat-card p {
    margin: 1rem 0 0;
    font-size: 2rem;
    color: #3498db;
    font-weight: bold;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.messages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.message-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.message-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-edit, .btn-delete, .btn-reply {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-edit {
    background: #3498db;
    color: white;
}

.btn-delete {
    background: #e74c3c;
    color: white;
}

.btn-reply {
    background: #2ecc71;
    color: white;
    text-decoration: none;
}

.logout-btn {
    background: #e74c3c;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: 5px;
    position: relative;
}

.close {
    position: absolute;
    right: 10px;
    top: 5px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.orders-section {
    background: #1e1e1e;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    overflow-x: auto;
}

.orders-section table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.orders-section th, .orders-section td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #333;
    white-space: normal;
    vertical-align: top;
}

.orders-section td {
    font-size: 14px;
    line-height: 1.4;
}

.orders-section tr:hover {
    background: #252525;
}

.btn-view {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.modal-content {
    background: #1e1e1e;
    margin: 10% auto;
    padding: 20px;
    width: 80%;
    max-width: 700px;
    border-radius: 8px;
    position: relative;
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    cursor: pointer;
}
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <div class="logo">DJ Store Admin</div>
            <ul>
                <li><a href="#dashboard" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="#products"><i class="fas fa-box"></i> Продукти</a></li>
                <li><a href="#users"><i class="fas fa-users"></i> Потребители</a></li>
                <li><a href="#messages"><i class="fas fa-envelope"></i> Съобщения</a></li>
            </ul>
            <div class="admin-profile">
                <span>Admin: <?php echo htmlspecialchars($adminSession['username']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Изход</a>
            </div>
        </nav>

        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>Общо потребители</h3>
                <p id="total_users"><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Продукти в кошницата</h3>
                <p id="total_cart_items"><?php echo $stats['total_cart_items']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope"></i>
                <h3>съобщения</h3>
                <p id="total_messages"><?php echo $stats['total_messages']; ?></p>
            </div>
        </div>

        <div class="product-form">
            <h2><i class="fas fa-plus-circle"></i> Добавете нов продукт</h2>
            <form id="addProductForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <div class="form-group">
                    <label>Име на продукта:</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Цена:</label>
                    <input type="number" name="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Обяснение:</label>
                    <textarea name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label>категория:</label>
                    <select name="category" required>
                        <option value="mixers">DJ миксери</option>
                        <option value="players">DJ плейъри</option>
                        <option value="controllers">DJ контролери</option>
                        <option value="turntables">DJ грамофони</option>
                        <option value="effects">DJ ефектори / семплери</option>
                        <option value="headphones">DJ слушалки</option>
                        <option value="cases">DJ кейсове</option>
                        <option value="stands">DJ Стойки</option>
                        <option value="software">DJ софтуер</option>
                        <option value="cables">DJ кабели</option>
                        <option value="monitors">Мониторни колони</option>
                        <option value="production">Музикално продуциране</option>
                        <option value="usb">USB Flash памети</option>
                        <option value="tables">DJ маси</option>
                        <option value="accessories">Аксесоари</option>
                        <option value="hearing">Защита на Слуха</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Изображение на продукта:</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>

                <button type="submit">Добавете продукт</button>
            </form>
        </div>

        <div class="product-grid">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p>Цена: <?php echo number_format($product['price'], 2); ?> лв</p>
                        <p>Категория: <?php echo htmlspecialchars($product['category']); ?></p>
                        <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-edit"></i> Редактирай
                        </button>
                        <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-trash"></i> Изтриване
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Ürün düzenleme modalı -->
        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditProductModal()">&times;</span>
                <h2>Редактиране на продукта</h2>
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_product">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    
                    <div class="form-group">
                        <label>Име на продукта:</label>
                        <input type="text" name="name" id="edit_product_name" required>
                    </div>

                    <div class="form-group">
                        <label>Цена:</label>
                        <input type="number" name="price" id="edit_product_price" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label>Описание:</label>
                        <textarea name="description" id="edit_product_description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Категория:</label>
                        <select name="category" id="edit_product_category" required>
                            <option value="mixers">DJ миксери</option>
                            <option value="players">DJ плейъри</option>
                            <option value="controllers">DJ контролери</option>
                            <option value="turntables">DJ грамофони</option>
                            <option value="effects">DJ ефектори</option>
                            <option value="headphones">DJ слушалки</option>
                            <option value="cases">DJ кейсове</option>
                            <option value="stands">DJ стойки</option>
                            <option value="software">DJ софтуер</option>
                            <option value="cables">DJ кабели</option>
                            <option value="monitors">Мониторни колони</option>
                            <option value="production">Музикално продуциране</option>
                            <option value="usb">USB Flash</option>
                            <option value="tables">DJ маси</option>
                            <option value="accessories">Аксесоари</option>
                            <option value="hearing">Защита на слуха</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ново изображение (незадължително):</label>
                        <input type="file" name="image" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>Текущо изображение:</label>
                        <img id="current_product_image" src="" alt="Current product image" style="max-width: 200px;">
                    </div>

                    <button type="submit">Запази промените</button>
                </form>
            </div>
        </div>

        <div class="users-section">
            <h2><i class="fas fa-users"></i> Наскоро регистрирани потребители</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Потребителско име</th>
                        <th>Email</th>
                        <th>Роля</th>
                        <th>Редактиране</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $recent_users->fetch_assoc()): ?>
                    <tr data-user-id="<?php echo $user['id']; ?>">
                        <td>#<?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role'] ?? 'user'; ?></td>
                        <td>
                            <button onclick="editUser(<?php echo $user['id']; ?>)" class="btn-edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="editUserModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Потребителя редактиране</h2>
                <form id="editUserForm">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label>Потребителско име:</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Роля:</label>
                        <select id="edit_role" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit">актуализация</button>
                </form>
            </div>
        </div>

        <div class="cart-items">
            <h2><i class="fas fa-shopping-cart"></i> Продукти в кошницата</h2>
            <table>
                <thead>
                    <tr>
                        <th>Потребител</th>
                        <th>Продукт</th>
                        <th>количество</th>
                        <th>Цена</th>
                        <th>Общо</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $cart_items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'], 2); ?> лв</td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> лв</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="recent-messages">
            <h2><i class="fas fa-envelope"></i> Последни публикации</h2>
            <div class="messages-grid">
                <?php while($message = $recent_messages->fetch_assoc()): ?>
                <div class="message-card" data-message-id="<?php echo $message['id']; ?>">
                    <div class="message-header">
                        <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                        <span><?php echo htmlspecialchars($message['email']); ?></span>
                    </div>
                    <div class="message-content">
                        <p><strong>Тема:</strong> <?php echo htmlspecialchars($message['subject']); ?></p>
                        <p><?php echo htmlspecialchars($message['message']); ?></p>
                    </div>
                    <div class="message-actions">
                        <button onclick="deleteMessage(<?php echo $message['id']; ?>)" class="btn-delete">
                            <i class="fas fa-trash"></i> Изтриване
                        </button>
                        <a href="mailto:<?php echo $message['email']; ?>" class="btn-reply">
                            <i class="fas fa-reply"></i> Отговор
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="orders-section">
            <h2><i class="fas fa-shopping-bag"></i> Поръчки</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID на поръчката</th>
                        <th>Клиент</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Адрес</th>
                        <th>Продукти</th>
                        <th>Общо</th>
                        <th>Ситуация</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $orders->fetch_assoc()): 
                        $cart_items = json_decode($order['cart_items'], true);
                        $items_list = '';
                        foreach($cart_items as $item) {
                            $items_list .= sprintf(
                                "%s (%d Броя - %.2f лв)<br>",
                                htmlspecialchars($item['product_name']),
                                $item['quantity'],
                                $item['price']
                            );
                        }
                    ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['name']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($order['address'])); ?></td>
                        <td><?php echo $items_list; ?></td>
                        <td><?php echo number_format($order['total_price'], 2); ?> лв</td>
                        <td><?php echo $order['status']; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div id="orderModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="orderDetails"></div>
            </div>
        </div>
    </div>

    <script src="admin_script.js"></script>
</body>
</html>