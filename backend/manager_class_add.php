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

$class_code = $data['class_id'] ?? '';
$subject_id = $data['subject_id'] ?? '';
$teacher_id = $data['teacher_id'] ?? '';
$room = $data['class_room'] ?? '';       // Phòng học
$schedule_time = $data['schedule_time'] ?? '';
$format = $data['format'] ?? '';
$day_of_week = $data['day_of_week'] ?? ''; 

$semester_id = 1; 
$max_students = 60; 

// 4. Validation cơ bản
if (empty($class_code) || empty($subject_id) || empty($teacher_id) || empty($room) || empty($schedule_time) || empty($format) || empty($day_of_week)) {
    http_response_code(400);
    $response['message'] = 'Vui lòng điền đầy đủ thông tin.';
    echo json_encode($response);
    exit();
}

// Tách giờ
$times = explode('-', $schedule_time);
$start_time = isset($times[0]) ? trim($times[0]) : null;
$end_time = isset($times[1]) ? trim($times[1]) : null;

if (!$start_time || !$end_time) {
     http_response_code(400);
     $response['message'] = 'Lỗi định dạng giờ học.';
     echo json_encode($response);
     exit();
}

// --- CHUYỂN ĐỔI NGÀY SANG SỐ ---
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
    // A. Kiểm tra trùng Mã lớp
    $stmt_check_code = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
    $stmt_check_code->execute([$class_code]);
    if ($stmt_check_code->fetch()) {
        http_response_code(409);
        $response['message'] = "Mã lớp '$class_code' đã tồn tại.";
        echo json_encode($response);
        exit();
    }

    // B. Kiểm tra trùng lịch Giáo viên (Teacher Overlap)
    $sql_teacher_conflict = "SELECT c.class_code 
                             FROM classes c
                             JOIN schedules s ON c.id = s.class_id
                             WHERE c.teacher_id = ? 
                               AND c.semester_id = ? 
                               AND s.day_of_week = ? 
                               AND (s.start_time < ? AND s.end_time > ?)";
    
    $stmt_t_conflict = $pdo->prepare($sql_teacher_conflict);
    $stmt_t_conflict->execute([$teacher_id, $semester_id, $day_int, $end_time, $start_time]);
    $t_conflict = $stmt_t_conflict->fetch();

    if ($t_conflict) {
        http_response_code(409); 
        $response['message'] = "Giáo viên bị trùng lịch với lớp " . $t_conflict['class_code'] . ".";
        echo json_encode($response);
        exit();
    }

    // ============================================================
    // C. [MỚI] KIỂM TRA TRÙNG PHÒNG HỌC (Room Overlap)
    // Logic: Phòng học này (room) vào ngày này (day_int) đã có lớp nào chiếm chưa?
    // ============================================================
    $sql_room_conflict = "SELECT c.class_code 
                          FROM classes c
                          JOIN schedules s ON c.id = s.class_id
                          WHERE s.room = ? 
                            AND c.semester_id = ? 
                            AND s.day_of_week = ? 
                            AND (s.start_time < ? AND s.end_time > ?)";
    
    $stmt_r_conflict = $pdo->prepare($sql_room_conflict);
    // Tham số: room, semester_id, day_int, End_New, Start_New
    $stmt_r_conflict->execute([$room, $semester_id, $day_int, $end_time, $start_time]);
    $r_conflict = $stmt_r_conflict->fetch();

    if ($r_conflict) {
        http_response_code(409); 
        $response['message'] = "Xung đột phòng học! Phòng '$room' đã có lớp " . $r_conflict['class_code'] . " học vào giờ này.";
        echo json_encode($response);
        exit();
    }
    // ============================================================

    // --- THỰC HIỆN INSERT ---
    $pdo->beginTransaction();

    $stmt_class = $pdo->prepare("INSERT INTO classes (class_code, subject_id, semester_id, teacher_id, format, max_students) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_class->execute([$class_code, $subject_id, $semester_id, $teacher_id, $format, $max_students]);
    
    $new_class_id = $pdo->lastInsertId();

    $stmt_schedule = $pdo->prepare("INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)");
    $stmt_schedule->execute([$new_class_id, $day_int, $start_time, $end_time, $room]);

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Thêm lớp học thành công!";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>