<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$student_id = $_GET['id'] ?? null;

try {
    $stmtInfo = $pdo->prepare("SELECT student_code, name FROM students WHERE id = ?");
    $stmtInfo->execute([$student_id]);
    $student = $stmtInfo->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Sinh viên không tồn tại']);
        exit;
    }

    $sql = "SELECT c.class_code, s.name AS subject_name, c.format, CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
            sch.day_of_week, sch.start_time, sch.end_time, sch.room, e.midterm_score, e.final_score
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN schedules sch ON c.id = sch.class_id
            WHERE e.student_id = ? ORDER BY sch.day_of_week ASC";

    $stmtClasses = $pdo->prepare($sql);
    $stmtClasses->execute([$student_id]);
    $classes = $stmtClasses->fetchAll();

    $days = [2 => 'Thứ Hai', 3 => 'Thứ Ba', 4 => 'Thứ Tư', 5 => 'Thứ Năm', 6 => 'Thứ Sáu', 7 => 'Thứ Bảy', 8 => 'Chủ Nhật'];
    foreach ($classes as &$cls) {
        $cls['day_text'] = $days[$cls['day_of_week']] ?? 'Chưa xếp';
        $cls['time_text'] = substr($cls['start_time'], 0, 5) . ' - ' . substr($cls['end_time'], 0, 5);
    }

    echo json_encode(['success' => true, 'student' => $student, 'classes' => $classes]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>