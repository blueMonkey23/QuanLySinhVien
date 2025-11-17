<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận GET';
    echo json_encode($response);
    exit();
}

try {
    // 1. VIẾT CÂU LỆNH SQL
    // Câu lệnh này JOIN nhiều bảng để lấy thông tin chúng ta cần
    $sql = "SELECT 
                c.id AS class_id,
                c.class_code,
                c.max_students,
                s.name AS subject_name,
                sem.name AS semester_name,
                sem.end_date,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS current_students
            FROM classes c
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN semesters sem ON c.semester_id = sem.id
            ORDER BY sem.start_date DESC, s.name ASC";

    // 2. THỰC THI TRUY VẤN 
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $classes = $stmt->fetchAll();

    // 3. TRẢ VỀ KẾT QUẢ JSON
    $response['success'] = true;
    $response['data'] = $classes;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>