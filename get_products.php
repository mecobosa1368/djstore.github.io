<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'dj_store');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Връзката е неуспешна: ' . $conn->connect_error]));
}

$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
$conn->close(); 