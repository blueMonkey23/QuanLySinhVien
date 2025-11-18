<?php
require 'config.php';

// 1. Chỉ chấp nhận phương thức POST (hoặc DELETE)
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

// 2. Kiểm tra quyền
get_current_user_id(); 

// 3. Lấy ID cần xóa
$input = json_decode(file_get_contents('php://input'), true);
$class_id = $input['id'] ?? null;

if (!$class_id) {
    http_response_code(400);
    $response['message'] = 'Thiếu ID lớp học cần xóa.';
    echo json_encode($response);
    exit();
}

try {
    // 4. Thực hiện xóa
    // Nhờ CSDL có Constraint ON DELETE CASCADE, lệnh này sẽ xóa sạch dữ liệu liên quan
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = "Đã xóa thành công lớp học.";
    } else {
        http_response_code(404);
        $response['message'] = "Không tìm thấy lớp học hoặc đã bị xóa trước đó.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>