<?php
session_start();

// Session ID'yi al
$session_id = $_GET['session_id'] ?? '';

if (!empty($session_id)) {
    // Sadece ilgili session'ı sil
    if (isset($_SESSION['admin_' . $session_id])) {
        unset($_SESSION['admin_' . $session_id]);
    }
    if (isset($_SESSION['user_' . $session_id])) {
        unset($_SESSION['user_' . $session_id]);
    }
} else {
    // Session ID verilmemişse tüm session'ı temizle
    session_destroy();
}

header("Location: nacstr.html"); 
exit();
?>
