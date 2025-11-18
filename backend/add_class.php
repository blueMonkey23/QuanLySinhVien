<?php
require 'config.php'; // Đảm bảo file này đã cấu hình CORS chuẩn

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
// Frontend gửi format { data: { ... } } nên ta lấy $input['data']
$data = $input['data'] ?? null;

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu trống']);
    exit;
}

// Các trường bắt buộc
$required = ['class_id', 'subject_id', 'teacher_id', 'day_of_week', 'schedule_time', 'class_room'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Thiếu trường: $field"]);
        exit;
    }
}

try {
    // 1. Lấy Học kỳ hiện tại (is_current = 1)
    $stmtSem = $pdo->prepare("SELECT id FROM semesters WHERE is_current = 1 LIMIT 1");
    $stmtSem->execute();
    $sem = $stmtSem->fetch();
    if (!$sem) throw new Exception("Chưa cấu hình học kỳ hiện tại trong Database");

    // 2. Tìm ID Môn học từ Mã môn (subject_code)
    $stmtSub = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $stmtSub->execute([$data['subject_id']]); // subject_id ở đây là Mã môn (ví dụ IT01)
    $subject = $stmtSub->fetch();
    if (!$subject) throw new Exception("Mã môn học '{$data['subject_id']}' không tồn tại");

    // 3. Tìm ID Giảng viên từ Mã GV (teacher_code)
    $stmtTea = $pdo->prepare("SELECT id FROM teachers WHERE teacher_code = ?");
    $stmtTea->execute([$data['teacher_id']]);
    $teacher = $stmtTea->fetch();
    if (!$teacher) throw new Exception("Mã giảng viên '{$data['teacher_id']}' không tồn tại");

    // 4. Kiểm tra trùng Mã lớp
    $stmtCheck = $pdo->prepare("SELECT id FROM classes WHERE class_code = ?");
    $stmtCheck->execute([$data['class_id']]);
    if ($stmtCheck->fetch()) throw new Exception("Mã lớp '{$data['class_id']}' đã tồn tại");

    // 5. Xử lý giờ học
    $timeMap = [
        '1' => ['07:30:00', '11:30:00'],
        '2' => ['12:45:00', '16:00:00'],
        '3' => ['18:30:00', '21:30:00']
    ];
    $times = $timeMap[$data['schedule_time']] ?? null;
    if (!$times) throw new Exception("Ca học không hợp lệ");

    // Map hình thức
    $format = ($data['format'] == '2') ? 'Trực tuyến' : 'Trực tiếp';

    // BẮT ĐẦU TRANSACTION
    $pdo->beginTransaction();

    // Insert Class
    $sqlClass = "INSERT INTO classes (class_code, subject_id, semester_id, teacher_id, format, max_students) 
                 VALUES (?, ?, ?, ?, ?, 60)";
    $stmtClass = $pdo->prepare($sqlClass);
    $stmtClass->execute([
        $data['class_id'], 
        $subject['id'], 
        $sem['id'], 
        $teacher['id'], 
        $format
    ]);
    $newClassId = $pdo->lastInsertId();

    // Insert Schedule
    $sqlSch = "INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room) 
               VALUES (?, ?, ?, ?, ?)";
    $stmtSch = $pdo->prepare($sqlSch);
    $stmtSch->execute([
        $newClassId,
        $data['day_of_week'],
        $times[0],
        $times[1],
        $data['class_room']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Thêm lớp học thành công!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>