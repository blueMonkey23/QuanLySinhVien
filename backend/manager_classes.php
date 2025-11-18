<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ';
    echo json_encode($response);
    exit();
}

try {
    // 1. Lấy tham số từ URL (Đã bỏ $course)
    $search = $_GET['q'] ?? '';        
    $subject = $_GET['subject'] ?? ''; 

    // 2. Xây dựng câu truy vấn động
    $sql = "SELECT 
                c.id AS class_id,
                c.class_code,
                c.is_locked,
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
            WHERE 1=1"; 

    $params = [];

    // A. Lọc theo từ khóa 
    if (!empty($search)) {
        $sql .= " AND (c.class_code LIKE ? OR s.name LIKE ? OR CONCAT(t.first_name, ' ', t.last_name) LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // (ĐÃ XÓA PHẦN LỌC THEO KHÓA Ở ĐÂY)

    // B. Lọc theo Môn học (ID)
    if (!empty($subject) && $subject !== 'all') {
        $sql .= " AND c.subject_id = ?";
        $params[] = $subject;
    }

    $sql .= " ORDER BY sem.start_date DESC, s.name ASC";

    // 3. Thực thi
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

    $response['success'] = true;
    $response['data'] = $classes;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>