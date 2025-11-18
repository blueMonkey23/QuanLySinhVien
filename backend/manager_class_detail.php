<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CẤU HÌNH CORS
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require 'config.php'; // Gọi file config (đã sửa CORS)

$class_id = $_GET['id'] ?? 0;

try {
    // 1. Lấy thông tin lớp
    $sqlClass = "SELECT 
                    c.class_code, c.format, c.max_students,
                    s.name AS subject_name,
                    sem.name AS semester_name,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                    sch.day_of_week, sch.start_time, sch.end_time, sch.room,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS current_students
                FROM classes c
                LEFT JOIN subjects s ON c.subject_id = s.id
                LEFT JOIN teachers t ON c.teacher_id = t.id
                LEFT JOIN semesters sem ON c.semester_id = sem.id
                LEFT JOIN schedules sch ON c.id = sch.class_id
                WHERE c.id = ?";
    
    $stmt = $pdo->prepare($sqlClass);
    $stmt->execute([$class_id]);
    $class_info = $stmt->fetch();

    if (!$class_info) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy lớp học']);
        exit;
    }

    // 2. Lấy danh sách sinh viên
    $sqlStudents = "SELECT 
                        e.id AS enrollment_id,
                        st.student_code, 
                        st.name,
                        e.midterm_score, 
                        e.final_score
                    FROM enrollments e
                    JOIN students st ON e.student_id = st.id
                    WHERE e.class_id = ?";
    $stmtSt = $pdo->prepare($sqlStudents);
    $stmtSt->execute([$class_id]);
    $students = $stmtSt->fetchAll();

    echo json_encode([
        'success' => true, 
        'data' => [
            'class_info' => $class_info,
            'students' => $students
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>