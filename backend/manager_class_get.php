<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405); echo json_encode(['message' => 'Method Not Allowed']); exit();
}

$id = $_GET['id'] ?? 0;

try {
    // Lấy thông tin lớp và lịch học
    $sql = "SELECT c.id, c.class_code, c.subject_id, c.teacher_id, c.format, c.is_locked,
                   s.day_of_week, s.start_time, s.end_time, s.room
            FROM classes c
            LEFT JOIN schedules s ON c.id = s.class_id
            WHERE c.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        http_response_code(404);
        $response['message'] = 'Không tìm thấy lớp học.';
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi: " . $e->getMessage();
}

echo json_encode($response);
?>