<?php
require 'config.php';

$sid = $_GET['id'] ?? 0;

try {
    // Lấy tên SV
    $st = $pdo->prepare("SELECT name, student_code FROM students WHERE id=?");
    $st->execute([$sid]);
    $student = $st->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy SV']);
        exit;
    }

    // Lấy lịch
    $sql = "SELECT c.class_code, s.name AS subject_name, c.format, 
                   CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                   sch.day_of_week, sch.start_time, sch.end_time, sch.room,
                   e.midterm_score, e.final_score
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN schedules sch ON c.id = sch.class_id
            WHERE e.student_id = ?
            ORDER BY sch.day_of_week";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sid]);
    $classes = $stmt->fetchAll();

    // Format dữ liệu hiển thị
    $days = [2=>'Thứ 2', 3=>'Thứ 3', 4=>'Thứ 4', 5=>'Thứ 5', 6=>'Thứ 6', 7=>'Thứ 7', 8=>'CN'];
    foreach ($classes as &$c) {
        $c['day_text'] = $days[$c['day_of_week']] ?? 'N/A';
        $c['time_text'] = substr($c['start_time'],0,5) . '-' . substr($c['end_time'],0,5);
    }

    echo json_encode(['success' => true, 'student' => $student, 'classes' => $classes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>