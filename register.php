<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'dj_store'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "Паролите не съвпадат.";
        exit();
    }

    // Check if username or email already exists
    $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        echo "Този потребител или имейл вече съществува.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT); 

    $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";
    if ($conn->query($query) === TRUE) {
        echo "Регистрирането е успешно.Върни се за да влезеш.";
    } else {
        echo "Грешка: " . $conn->error;
    }

    $conn->close();
}
?>
