<?php
require 'config.php';
try {   
    session_unset();
    session_destroy();
    $response['success'] = true;
    $response['message'] = 'Đăng xuất thành công!';

} catch (Exception $e) {
    $response['message'] = 'Lỗi khi đăng xuất: ' . $e->getMessage();
}
echo json_encode($response);
?>