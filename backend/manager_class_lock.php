<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ';
    echo json_encode($response);
    exit();
}

get_current_user_id(); // Kiểm tra đăng nhập

$input = json_decode(file_get_contents('php://input'), true);
$class_id = $input['id'] ?? null;

if (!$class_id) {
    http_response_code(400);
    $response['message'] = 'Thiếu ID lớp học.';
    echo json_encode($response);
    exit();
}

try {
    // 1. Kiểm tra trạng thái hiện tại
    $stmt = $pdo->prepare("SELECT is_locked FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();

    if (!$class) {
        http_response_code(404);
        $response['message'] = "Không tìm thấy lớp học.";
        echo json_encode($response);
        exit();
    }

    // 2. Đảo ngược trạng thái (0->1, 1->0)
    $new_status = $class['is_locked'] == 1 ? 0 : 1;

    $updateStmt = $pdo->prepare("UPDATE classes SET is_locked = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $class_id]);

    $response['success'] = true;
    $status_text = $new_status == 1 ? "Đã khóa" : "Đã mở khóa";
    $response['message'] = "Thành công: Lớp học $status_text.";
    $response['new_status'] = $new_status;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>