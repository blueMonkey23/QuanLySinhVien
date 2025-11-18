<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? null;

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();
<<<<<<< Updated upstream
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $response['success'] = true;
        $response['message'] = 'Đăng nhập thành công!';
        $response['data'] = [
            'user_id' => $user['id'],
            'email' => $user['email']
        ];
=======

    if ($user && password_verify($data['password'], $user['password_hash'])) {
        // Lấy quyền từ bảng role_user và roles
        $stmtRole = $pdo->prepare("
            SELECT r.name 
            FROM roles r 
            JOIN role_user ru ON r.id = ru.role_id 
            WHERE ru.user_id = ? LIMIT 1
        ");
        $stmtRole->execute([$user['id']]);
        $role = $stmtRole->fetchColumn() ?: 'student';

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $role;

        echo json_encode([
            'success' => true, 
            'message' => 'Đăng nhập thành công',
            'data' => ['role' => $role, 'fullname' => $user['name']]
        ]);
>>>>>>> Stashed changes
    } else {
        echo json_encode(['success' => false, 'message' => 'Email hoặc mật khẩu sai']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>