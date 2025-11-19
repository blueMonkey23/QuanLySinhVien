<?php
// backend/get_student_classes.php
require 'config.php';

$sid = $_GET['id'] ?? 0;

try {
    // Lấy tên sinh viên
    $st = $pdo->prepare("SELECT name, student_code FROM students WHERE id=?");
    $st->execute([$sid]);
    $student = $st->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sinh viên']);
        exit;
    }

    // Lấy danh sách lớp và lịch học (JOIN 5 bảng)
    $sql = "SELECT 
                c.class_code, 
                s.name AS subject_name, 
                c.format, 
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                sch.day_of_week, 
                sch.start_time, 
                sch.end_time, 
                sch.room,
                e.midterm_score, 
                e.final_score
            FROM enrollments e
            JOIN classes c ON e.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN schedules sch ON c.id = sch.class_id
            WHERE e.student_id = ?
            ORDER BY sch.day_of_week ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sid]);
    $classes = $stmt->fetchAll();

    // Format lại thứ ngày và giờ cho đẹp
    $days = [2=>'Thứ Hai', 3=>'Thứ Ba', 4=>'Thứ Tư', 5=>'Thứ Năm', 6=>'Thứ Sáu', 7=>'Thứ Bảy', 8=>'Chủ Nhật'];
    
    foreach ($classes as &$cls) {
        $cls['day_text'] = $days[$cls['day_of_week']] ?? 'Chưa xếp';
        $cls['time_text'] = substr($cls['start_time'], 0, 5) . ' - ' . substr($cls['end_time'], 0, 5);
    }

    echo json_encode(['success' => true, 'student' => $student, 'classes' => $classes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>