<?php
$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$admin_password = password_hash('admin123', PASSWORD_DEFAULT);


$sql = "INSERT INTO users (username, email, password, role) 
        VALUES ('admin', 'admin@djstore.com', '$admin_password', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "Администраторът е създаден успешно.<br>";
    echo "Потребителско име: admin<br>";
    echo "Парола: admin123";
} else {
    echo "Грешка: " . $conn->error;
}

$conn->close();
?> 