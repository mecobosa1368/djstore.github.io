<?php
session_start();
header('Content-Type: application/json');

function getUserSession() {
    foreach($_SESSION as $key => $value) {
        if(strpos($key, 'user_') === 0 || strpos($key, 'admin_') === 0) {
            return $value;
        }
    }
    return null;
}

$userSession = getUserSession();
echo json_encode(['loggedIn' => ($userSession !== null)]);
?>
