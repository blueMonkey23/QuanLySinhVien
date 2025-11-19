<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? null;

try {
    // 1. Lấy HK hiện tại
    $sem = $pdo->query("SELECT id FROM semesters WHERE is_current = 1 LIMIT 1")->fetch();
    if (!$sem) throw new Exception("Chưa có học kỳ nào được kích hoạt");

    // 2. Tìm Subject ID
    $sub = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
    $sub->execute([$data['subject_id']]);
    $subject = $sub->fetch();
    if (!$subject) throw new Exception("Mã môn học không tồn tại");

    // 3. Tìm Teacher ID
    $tea = $pdo->prepare("SELECT id FROM teachers WHERE teacher_code = ?");
    $tea->execute([$data['teacher_id']]);
    $teacher = $tea->fetch();
    if (!$teacher) throw new Exception("Mã giảng viên không tồn tại");

    // 4. Time map
    $timeMap = ['1'=>['07:30:00','11:30:00'], '2'=>['12:45:00','16:00:00'], '3'=>['18:30:00','21:30:00']];
    $times = $timeMap[$data['schedule_time']];
    $format = ($data['format'] == '2') ? 'Trực tuyến' : 'Trực tiếp';

    $pdo->beginTransaction();
    
    $sql1 = "INSERT INTO classes (class_code, subject_id, semester_id, teacher_id, format, max_students) VALUES (?, ?, ?, ?, ?, 60)";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$data['class_id'], $subject['id'], $sem['id'], $teacher['id'], $format]);
    $cid = $pdo->lastInsertId();

    $sql2 = "INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$cid, $data['day_of_week'], $times[0], $times[1], $data['class_room']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Thêm lớp thành công']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>