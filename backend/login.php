<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405); 
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['data']['email'] ?? '';
$password = $input['data']['password'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $response['success'] = true;
        $response['message'] = 'Đăng nhập thành công!';
        $response['data'] = [
            'user_id' => $user['id'],
            'email' => $user['email']
        ];
    } else {
        http_response_code(401);
        $response['message'] = 'Email hoặc mật khẩu không chính xác!';
    }

} catch (PDOException $e) {
    http_response_code(500); 
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}
echo json_encode($response);
?>