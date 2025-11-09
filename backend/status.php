<?php
require 'config.php';
try {
    if (isset($_SESSION['user_id'])) {
        $response['success'] = true;
        $response['data'] = [
            'logged_in' => true,
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email']
        ];
    } else {
        $response['success'] = true; 
        $response['data'] = ['logged_in' => false];
    }
} catch (Exception $e) {
    $response['message'] = 'Lỗi khi kiểm tra trạng thái: ' . $e->getMessage();
}

echo json_encode($response);
?>