<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

get_current_user_id(); // Kiểm tra quyền

$input = json_decode(file_get_contents('php://input'), true);
$class_id = $input['class_id'] ?? null;
$student_code = $input['student_code'] ?? null;
$action = $input['action'] ?? null; // 'add' hoặc 'remove'

if (!$class_id || !$student_code || !$action) {
    http_response_code(400);
    $response['message'] = 'Thiếu ID lớp, Mã sinh viên hoặc hành động.';
    echo json_encode($response);
    exit();
}

try {
    // 1. Tìm ID sinh viên
    $stmt_student = $pdo->prepare("SELECT id FROM students WHERE student_code = ?");
    $stmt_student->execute([$student_code]);
    $student = $stmt_student->fetch();
    
    if (!$student) {
        http_response_code(404);
        $response['message'] = "Lỗi: Không tìm thấy sinh viên với mã {$student_code}.";
        echo json_encode($response);
        exit();
    }
    $student_id = $student['id'];

    // 2. Xử lý hành động Thêm (ADD)
    if ($action === 'add') {
        
        // Kiểm tra xem sinh viên đã được ghi danh chưa
        $stmt_check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND class_id = ?");
        $stmt_check->execute([$student_id, $class_id]);
        if ($stmt_check->fetch()) {
            http_response_code(409);
            $response['message'] = "Sinh viên đã được ghi danh vào lớp này.";
            echo json_encode($response);
            exit();
        }

        // Kiểm tra sĩ số lớp
        $stmt_capacity = $pdo->prepare("SELECT max_students, (SELECT COUNT(*) FROM enrollments WHERE class_id = ?) AS current_students FROM classes WHERE id = ?");
        $stmt_capacity->execute([$class_id, $class_id]);
        $capacity = $stmt_capacity->fetch();

        if ($capacity && $capacity['current_students'] >= $capacity['max_students']) {
            http_response_code(409);
            $response['message'] = "Ghi danh thất bại: Lớp đã đủ sĩ số ({$capacity['max_students']}).";
            echo json_encode($response);
            exit();
        }

        // Thực hiện INSERT
        $stmt_insert = $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)");
        $stmt_insert->execute([$student_id, $class_id]);
        $response['message'] = "Thêm thành công sinh viên {$student_code} vào lớp.";
    }

    // 3. Xử lý hành động Xóa (REMOVE)
    else if ($action === 'remove') {
        $stmt_delete = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND class_id = ?");
        $stmt_delete->execute([$student_id, $class_id]);

        if ($stmt_delete->rowCount() > 0) {
            $response['message'] = "Đã xóa thành công sinh viên {$student_code} khỏi lớp.";
        } else {
            http_response_code(404);
            $response['message'] = "Sinh viên này không có trong danh sách ghi danh của lớp.";
            echo json_encode($response);
            exit();
        }
    }
    
    $response['success'] = true;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>