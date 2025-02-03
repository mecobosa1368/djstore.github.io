<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "dj_store";

    
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Неуспешна връзка с базата данни: ' . $conn->connect_error]);
        exit;
    }

    
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';

    
    $sql = "INSERT INTO ContactMessages (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Съобщението беше изпратено успешно.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Грешка при добавяне към базата данни: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Възникна грешка при подготовката на заявката: ' . $conn->error]);
    }

    $conn->close();
}
?>
