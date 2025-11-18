<?php
require 'config.php';

// 1. Kiểm tra phương thức
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

// 2. Kiểm tra quyền
get_current_user_id(); 

// 3. Lấy dữ liệu
$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? [];
$id = $input['id'] ?? 0; // ID của lớp đang sửa

// Map dữ liệu
$class_code = $data['class_id'] ?? '';
$subject_id = $data['subject_id'] ?? '';
$teacher_id = $data['teacher_id'] ?? '';
$room = $data['class_room'] ?? '';
$schedule_time = $data['schedule_time'] ?? '';
$format = $data['format'] ?? '';
$day_of_week = $data['day_of_week'] ?? ''; 
$semester_id = 1; 

// --- VALIDATION 1: Kiểm tra dữ liệu bắt buộc ---
if (!$id || empty($class_code) || empty($subject_id) || empty($teacher_id) || empty($room) || empty($schedule_time) || empty($format) || empty($day_of_week)) {
    http_response_code(400);
    $response['message'] = 'Vui lòng điền đầy đủ thông tin.';
    echo json_encode($response);
    exit();
}

// --- VALIDATION 2: Kiểm tra giờ học ---
$times = explode('-', $schedule_time);
$start_time = isset($times[0]) ? trim($times[0]) : null;
$end_time = isset($times[1]) ? trim($times[1]) : null;

if (!$start_time || !$end_time) {
     http_response_code(400);
     $response['message'] = 'Lỗi định dạng giờ học.';
     echo json_encode($response);
     exit();
}

// --- VALIDATION 3: Kiểm tra và Map ngày học ---
$day_map = [
    'Thứ Hai' => 2, 'Thứ Ba' => 3, 'Thứ Tư' => 4, 'Thứ Năm' => 5, 
    'Thứ Sáu' => 6, 'Thứ Bảy' => 7, 'Chủ Nhật' => 8
];
$day_int = $day_map[$day_of_week] ?? 0; 

if ($day_int === 0) {
    http_response_code(400);
    $response['message'] = 'Ngày học không hợp lệ.';
    echo json_encode($response);
    exit();
}

try {
    // --- VALIDATION 4: Kiểm tra xem lớp có bị KHÓA không? ---
    $stmt_lock = $pdo->prepare("SELECT is_locked FROM classes WHERE id = ?");
    $stmt_lock->execute([$id]);
    $is_locked = $stmt_lock->fetchColumn();
    
    if ($is_locked == 1) {
        http_response_code(403); // Forbidden
        $response['message'] = 'Lớp học đang bị KHÓA. Không được phép chỉnh sửa.';
        echo json_encode($response);
        exit();
    }

    // --- VALIDATION 5: Kiểm tra trùng Mã lớp (Trừ chính nó: AND id != ?) ---
    $stmt_check_code = $pdo->prepare("SELECT id FROM classes WHERE class_code = ? AND id != ?");
    $stmt_check_code->execute([$class_code, $id]);
    if ($stmt_check_code->fetch()) {
        http_response_code(409);
        $response['message'] = "Mã lớp '$class_code' đã tồn tại.";
        echo json_encode($response);
        exit();
    }

    // --- VALIDATION 6: Kiểm tra trùng lịch Giáo viên (Trừ chính nó: AND c.id != ?) ---
    $sql_t_conflict = "SELECT c.class_code 
                       FROM classes c
                       JOIN schedules s ON c.id = s.class_id
                       WHERE c.teacher_id = ? 
                         AND c.semester_id = ? 
                         AND s.day_of_week = ? 
                         AND (s.start_time < ? AND s.end_time > ?)
                         AND c.id != ?"; // <--- QUAN TRỌNG: Không so sánh với chính mình
    
    $stmt_t = $pdo->prepare($sql_t_conflict);
    $stmt_t->execute([$teacher_id, $semester_id, $day_int, $end_time, $start_time, $id]);
    $t_conflict = $stmt_t->fetch();

    if ($t_conflict) {
        http_response_code(409); 
        $response['message'] = "Giáo viên bị trùng lịch với lớp " . $t_conflict['class_code'];
        echo json_encode($response);
        exit();
    }

    // --- VALIDATION 7: Kiểm tra trùng Phòng học (Trừ chính nó: AND c.id != ?) ---
    $sql_r_conflict = "SELECT c.class_code 
                       FROM classes c
                       JOIN schedules s ON c.id = s.class_id
                       WHERE s.room = ? 
                         AND c.semester_id = ? 
                         AND s.day_of_week = ? 
                         AND (s.start_time < ? AND s.end_time > ?)
                         AND c.id != ?"; // <--- QUAN TRỌNG
    
    $stmt_r = $pdo->prepare($sql_r_conflict);
    $stmt_r->execute([$room, $semester_id, $day_int, $end_time, $start_time, $id]);
    $r_conflict = $stmt_r->fetch();

    if ($r_conflict) {
        http_response_code(409); 
        $response['message'] = "Phòng '$room' bị trùng lịch với lớp " . $r_conflict['class_code'];
        echo json_encode($response);
        exit();
    }

    // --- THỰC HIỆN UPDATE (TRANSACTION) ---
    $pdo->beginTransaction();

    // 1. Update bảng Classes
    $stmt_class = $pdo->prepare("UPDATE classes SET class_code = ?, subject_id = ?, teacher_id = ?, format = ? WHERE id = ?");
    $stmt_class->execute([$class_code, $subject_id, $teacher_id, $format, $id]);

    // 2. Update bảng Schedules
    // (Giả sử mỗi lớp chỉ có 1 lịch, ta update trực tiếp. Nếu nhiều lịch, nên xóa cũ thêm mới)
    $stmt_schedule = $pdo->prepare("UPDATE schedules SET day_of_week = ?, start_time = ?, end_time = ?, room = ? WHERE class_id = ?");
    $stmt_schedule->execute([$day_int, $start_time, $end_time, $room, $id]);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Cập nhật lớp học thành công!";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>