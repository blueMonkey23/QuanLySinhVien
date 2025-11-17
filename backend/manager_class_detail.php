<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận GET';
    echo json_encode($response);
    exit();
}

// 1. LẤY ID LỚP HỌC TỪ URL (?id=...)
$class_id = $_GET['id'] ?? 0;
if (!$class_id) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Lỗi: Không tìm thấy ID lớp học.';
    echo json_encode($response);
    exit();
}

try {
    // 2. TRUY VẤN THÔNG TIN CHI TIẾT LỚP HỌC
    $sql_class_info = "SELECT 
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
                       WHERE c.id = ?
                       LIMIT 1"; // Giả sử mỗi lớp chỉ có 1 lịch học chính

    $stmt_class_info = $pdo->prepare($sql_class_info);
    $stmt_class_info->execute([$class_id]);
    $class_info = $stmt_class_info->fetch();

    if (!$class_info) {
        http_response_code(404); // Not Found
        $response['message'] = 'Lỗi: Không tìm thấy thông tin lớp học với ID này.';
        echo json_encode($response);
        exit();
    }

    // 3. TRUY VẤN DANH SÁCH SINH VIÊN TRONG LỚP
    $sql_students = "SELECT 
                        s.id AS student_id,
                        s.student_code, s.name,
                        e.id AS enrollment_id, e.midterm_score, e.final_score
                     FROM students s
                     JOIN enrollments e ON s.id = e.student_id
                     WHERE e.class_id = ?
                     ORDER BY s.name ASC";
    
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute([$class_id]);
    $students = $stmt_students->fetchAll();

    // 4. GỘP KẾT QUẢ VÀ TRẢ VỀ JSON
    $response['success'] = true;
    $response['data'] = [
        'class_info' => $class_info,
        'students' => $students
    ];

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>