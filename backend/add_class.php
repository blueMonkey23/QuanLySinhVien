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
$data = $input['data'] ?? null;

if (!$data) {
    http_response_code(400); 
    $response['message'] = 'Dữ liệu không hợp lệ.';
    echo json_encode($response);
    exit();
}
$class_code = $data['class_id'] ?? '';
$subject_code_input = $data['subject_id'] ?? ''; 
$teacher_code_input = $data['teacher_id'] ?? ''; 
$format_val = $data['format'] ?? '';
$room = $data['class_room'] ?? '';
$schedule_time_val = $data['schedule_time'] ?? '';
$day_of_week = $data['day_of_week'] ?? '';
if (empty($class_code) || empty($subject_code_input) || empty($teacher_code_input) || empty($format_val) || 
    empty($room) || empty($schedule_time_val) || empty($day_of_week)) {
    http_response_code(400);
    $response['message'] = 'Vui lòng điền đầy đủ tất cả các trường bắt buộc.';
    echo json_encode($response);
    exit();
}

try {
    $stmt_sem = $pdo->prepare("SELECT id FROM semesters WHERE is_current = 1 LIMIT 1");
    $stmt_sem->execute();
    $semester = $stmt_sem->fetch();
    if (!$semester) {
        http_response_code(500); 
        $response['message'] = 'Không tìm thấy học kỳ hiện tại nào';
        echo json_encode($response);
        exit();
    }
    $current_semester_id = $semester['id'];
    $stmt_subject = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $stmt_subject->execute([$subject_code_input]);
    $subject = $stmt_subject->fetch();
    if (!$subject) {
        http_response_code(404); 
        $response['message'] = 'Không tìm thấy môn học nào với mã: ' . htmlspecialchars($subject_code_input);
        echo json_encode($response);
        exit();
    }
    $actual_subject_id = $subject['id'];
    $stmt_teacher = $pdo->prepare("SELECT id FROM teachers WHERE teacher_code = ?");
    $stmt_teacher->execute([$teacher_code_input]);
    $teacher = $stmt_teacher->fetch();
    if (!$teacher) {
        http_response_code(404); 
        $response['message'] = 'Không tìm thấy giảng viên nào với mã: ' . htmlspecialchars($teacher_code_input);
        echo json_encode($response);
        exit();
    }
    $actual_teacher_id = $teacher['id'];
    $stmt = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
    $stmt->execute([$class_code]);
    if ($stmt->fetch()) {
        http_response_code(409); 
        $response['message'] = 'Mã lớp học này đã tồn tại';
        echo json_encode($response);
        exit();
    }
    $start_time = ''; $end_time = '';
    switch ($schedule_time_val) {
        case '1': $start_time = '07:30:00'; $end_time = '11:30:00'; break;
        case '2': $start_time = '12:45:00'; $end_time = '16:00:00'; break;
        case '3': $start_time = '18:30:00'; $end_time = '21:30:00'; break;
        default: throw new Exception("Ca học không hợp lệ.");
    }
    $format_text = '';
    switch ($format_val) {
        case '1': $format_text = 'Trực tiếp'; break;
        case '2': $format_text = 'Trực tuyến'; break;
        default: throw new Exception("Hình thức học không hợp lệ.");
    }

    $pdo->beginTransaction();
    $sql_class = "INSERT INTO classes (class_code, subject_id, semester_id, teacher_id, format) 
                  VALUES (?, ?, ?, ?, ?)";
    $stmt_class = $pdo->prepare($sql_class);
    $stmt_class->execute([$class_code, $actual_subject_id, $current_semester_id, $actual_teacher_id, $format_text]);
    $new_class_id = $pdo->lastInsertId();
    $sql_schedule = "INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room) 
                       VALUES (?, ?, ?, ?, ?)";
    $stmt_schedule = $pdo->prepare($sql_schedule);
    $stmt_schedule->execute([$new_class_id, $day_of_week, $start_time, $end_time, $room]);
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = 'Thêm lớp học và lịch học thành công!';
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500); 
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>
// xu ly trung gio hoc cua giao vien va lop hoc