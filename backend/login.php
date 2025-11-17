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
        
        // Lấy vai trò (role) của người dùng
        $stmt_role = $pdo->prepare("
            SELECT r.name AS role_name 
            FROM roles r
            JOIN role_user ru ON r.id = ru.role_id
            WHERE ru.user_id = ?
            LIMIT 1
        ");
        $stmt_role->execute([$user['id']]);
        $role = $stmt_role->fetch();
        $role_name = $role ? $role['role_name'] : 'student'; // Mặc định là student nếu không tìm thấy

        // Thiết lập Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $role_name; 

        $response['success'] = true;
        $response['message'] = 'Đăng nhập thành công!';
        $response['data'] = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $role_name 
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