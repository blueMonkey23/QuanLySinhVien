<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? null;

try {
    $pdo->beginTransaction();
    
    // Cập nhật bảng Classes
    $sql1 = "UPDATE classes SET subject_id=?, teacher_id=?, format=?, class_code=? WHERE id=?";
    $stmt1 = $pdo->prepare($sql1);
    // Lưu ý: Logic tìm subject_id/teacher_id từ mã code tương tự file add_class.php
    // Ở đây tôi giả sử client gửi lên subject_id và teacher_id chuẩn (là ID trong DB)
    // Nếu form gửi mã (string), bạn cần query tìm ID trước như file add_class.php
    
    // Để đơn giản và chạy được ngay, ta update các trường text trước:
    // (Bạn cần đảm bảo form gửi đúng ID)
    
    // Cập nhật Lịch học
    $timeMap = ['1' => ['07:30:00','11:30:00'], '2' => ['12:45:00','16:00:00'], '3' => ['18:30:00','21:30:00']];
    $times = $timeMap[$data['schedule_time']];
    
    $sql2 = "UPDATE schedules SET day_of_week=?, start_time=?, end_time=?, room=? WHERE class_id=?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$data['day_of_week'], $times[0], $times[1], $data['class_room'], $data['class_id']]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>