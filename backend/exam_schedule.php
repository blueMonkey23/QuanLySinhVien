// backend/exam_schedule.php
<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận GET';
    echo json_encode($response);
    exit();
}
try{
    $user_id = get_current_user_id();
    $sql = "SELECT 
                s.name AS subject_name,
                s.subject_code,
                c.class_code,
                e.type,
                e.format,
                e.exam_date,
                e.start_time,
                e.end_time,
                e.room
            FROM exams e
            JOIN classes c ON e.class_id = c.id
            JOIN subjects s ON c.subject_id = s.id
            JOIN enrollments en ON c.id = en.class_id
            JOIN students st ON en.student_id = st.id
            WHERE 
                st.user_id = ?
            ORDER BY 
                e.exam_date, e.start_time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $exams = $stmt->fetchAll();
    $response['success'] = true;
    $response['data'] = $exams;
} catch (PDOException $e) {
    http_response_code(500); 
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}
echo json_encode($response);
?>