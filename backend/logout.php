<?php
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405); 
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}
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