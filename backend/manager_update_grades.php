<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

// BƯỚC BẮT BUỘC: Kiểm tra đăng nhập
get_current_user_id(); 

$input = json_decode(file_get_contents('php://input'), true);
$grades = $input['grades'] ?? [];

if (empty($grades) || !is_array($grades)) {
    http_response_code(400);
    $response['message'] = 'Dữ liệu điểm trống hoặc không đúng định dạng.';
    echo json_encode($response);
    exit();
}

try {
    // 1. Kiểm tra xem lớp có bị KHÓA không?
    // Lấy enrollment_id đầu tiên để truy ngược ra lớp học
    $first_enrollment_id = $grades[0]['enrollment_id'] ?? null;
    if ($first_enrollment_id) {
        $stmt_check_lock = $pdo->prepare("
            SELECT c.is_locked 
            FROM classes c 
            JOIN enrollments e ON c.id = e.class_id 
            WHERE e.id = ?
        ");
        $stmt_check_lock->execute([$first_enrollment_id]);
        $class_status = $stmt_check_lock->fetch();

        if ($class_status && $class_status['is_locked'] == 1) {
            http_response_code(403); // Forbidden
            $response['message'] = 'Lỗi: Lớp học này đang bị KHÓA. Không thể chỉnh sửa điểm số.';
            echo json_encode($response);
            exit();
        }
    }

    // 2. Bắt đầu transaction cập nhật
    $pdo->beginTransaction();

    // [UPDATE] Cập nhật 3 đầu điểm: giữa kỳ, cuối kỳ, chuyên cần
    $sql = "UPDATE enrollments SET midterm_score = ?, final_score = ?, diligence_score = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $total_updated = 0;

    foreach ($grades as $grade_entry) {
        $enrollment_id = $grade_entry['enrollment_id'] ?? null;
        
        // Xử lý giá trị: nếu rỗng hoặc không tồn tại thì set là null, nếu có thì lấy giá trị
        $midterm_score = (isset($grade_entry['midterm_score']) && $grade_entry['midterm_score'] !== '') ? $grade_entry['midterm_score'] : null;
        $final_score = (isset($grade_entry['final_score']) && $grade_entry['final_score'] !== '') ? $grade_entry['final_score'] : null;
        $diligence_score = (isset($grade_entry['diligence_score']) && $grade_entry['diligence_score'] !== '') ? $grade_entry['diligence_score'] : null;

        if ($enrollment_id) {
            // Validation: Điểm phải từ 0 đến 10 (nếu không phải null)
            if (($midterm_score !== null && ($midterm_score < 0 || $midterm_score > 10)) ||
                ($final_score !== null && ($final_score < 0 || $final_score > 10)) ||
                ($diligence_score !== null && ($diligence_score < 0 || $diligence_score > 10))) {
                
                $pdo->rollBack();
                http_response_code(400);
                $response['message'] = "Điểm số không hợp lệ (phải từ 0-10). ID Enrollment: $enrollment_id";
                echo json_encode($response);
                exit();
            }

            $stmt->execute([$midterm_score, $final_score, $diligence_score, $enrollment_id]);
            $total_updated += $stmt->rowCount();
        }
    }

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Đã lưu điểm thành công!";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>
