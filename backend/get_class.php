<?php
// Copy đoạn header CORS từ file config.php hoặc include config.php
require 'config.php'; 

$id = $_GET['id'] ?? null;
if(!$id) { echo json_encode(['success'=>false]); exit; }

try {
    // Lấy thông tin lớp + lịch học để điền vào form
    $sql = "SELECT 
                c.id, c.class_code, c.format, c.teacher_id, c.subject_id,
                sch.day_of_week, sch.start_time, sch.room, sch.id as schedule_id
            FROM classes c
            LEFT JOIN schedules sch ON c.id = sch.class_id
            WHERE c.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    // Xử lý giờ học để khớp với value của select box (1, 2, 3)
    $timeMap = ['07:30:00' => '1', '12:45:00' => '2', '18:30:00' => '3'];
    $data['schedule_time'] = $timeMap[$data['start_time']] ?? '';

    // Xử lý Subject_code và Teacher_code để hiển thị (nếu cần)
    // Nhưng ở form sửa ta cần ID để select, query trên đã lấy ID rồi.

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>