<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ';
    echo json_encode($response);
    exit();
}

// Lấy tham số lọc (nếu có)
// Ví dụ: ?teacher_id=1 hoặc ?room=P52
$teacher_id = $_GET['teacher_id'] ?? '';
$room = $_GET['room'] ?? '';
$semester_id = 1; // Mặc định học kỳ 1, nên lấy động trong thực tế

try {
    $sql = "SELECT 
                c.id AS class_id,
                c.class_code,
                s.name AS subject_name,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                sch.day_of_week,
                sch.start_time,
                sch.end_time,
                sch.room,
                c.format
            FROM classes c
            JOIN schedules sch ON c.id = sch.class_id
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            WHERE c.semester_id = ?";
    
    $params = [$semester_id];

    // Thêm điều kiện lọc nếu có
    if (!empty($teacher_id)) {
        $sql .= " AND c.teacher_id = ?";
        $params[] = $teacher_id;
    }
    
    if (!empty($room)) {
        $sql .= " AND sch.room LIKE ?";
        $params[] = "%$room%";
    }

    $sql .= " ORDER BY sch.day_of_week ASC, sch.start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedule_data = $stmt->fetchAll();

    $response['success'] = true;
    $response['data'] = $schedule_data;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>
