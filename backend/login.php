<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? null;

if (!$data || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Email và Mật khẩu']);
    exit;
}

try {
    // Lấy thông tin user
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($data['password'], $user['password_hash'])) {
        // Lấy vai trò (Role)
        $stmtRole = $pdo->prepare("
            SELECT r.name 
            FROM roles r 
            JOIN role_user ru ON r.id = ru.role_id 
            WHERE ru.user_id = ? LIMIT 1
        ");
        $stmtRole->execute([$user['id']]);
        $role = $stmtRole->fetchColumn() ?: 'student';

        // Lưu session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $role;

        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => ['role' => $role, 'fullname' => $user['name']]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email hoặc mật khẩu không chính xác']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>