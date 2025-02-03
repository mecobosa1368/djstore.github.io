<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['username']) && isset($_POST['password'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];

        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Benzersiz bir session ID oluştur
                $session_id = uniqid('session_', true);
                
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_' . $session_id] = [
                        'username' => $user['username'],
                        'user_id' => $user['id'],
                        'role' => $user['role']
                    ];
                    echo json_encode([
                        'success' => true,
                        'role' => 'admin',
                        'session_id' => $session_id,
                        'redirect' => 'admin_panel.php'
                    ]);
                } else {
                    $_SESSION['user_' . $session_id] = [
                        'username' => $user['username'],
                        'user_id' => $user['id'],
                        'role' => $user['role']
                    ];
                    echo json_encode([
                        'success' => true,
                        'role' => 'user',
                        'session_id' => $session_id,
                        'redirect' => 'index.html'
                    ]);
                }
            } else {

                echo json_encode([
                    'success' => false,
                    'message' => 'Грешна парола'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Потребителят не е намерен'
            ]);
        }
        exit();
    }
}

$conn->close();
?>