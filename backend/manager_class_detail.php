<?php
require 'config.php';

// Bật báo lỗi để debug (Tắt đi khi chạy thực tế nếu muốn)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận GET';
    echo json_encode($response);
    exit();
}

// 1. LẤY ID LỚP HỌC TỪ URL
$class_id = $_GET['id'] ?? 0;
if (!$class_id) {
    http_response_code(400);
    $response['message'] = 'Lỗi: Không tìm thấy ID lớp học.';
    echo json_encode($response);
    exit();
}

try {
    // 2. TRUY VẤN DỮ LIỆU
    // Logic sắp xếp: SUBSTRING_INDEX(st.name, ' ', -1) lấy từ cuối cùng (Tên) để sắp xếp
    $sql_full = "SELECT 
                    c.id AS class_id,
                    c.class_code,
                    c.subject_id,
                    s.name AS subject_name,
                    sem.name AS semester_name,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                    t.id AS teacher_id,
                    c.format,
                    c.max_students,
                    c.is_locked,
                    c.created_at,
                    sem.end_date,
                    sch.day_of_week,
                    sch.start_time,
                    sch.end_time,
                    sch.room,
                    e.id AS enrollment_id,
                    e.student_id,
                    st.student_code,
                    st.name AS student_name,
                    e.midterm_score,
                    e.final_score,
                    e.diligence_score,
                    (SELECT COUNT(*) FROM enrollments en WHERE en.class_id = c.id) AS current_students
                FROM classes c
                LEFT JOIN subjects s ON c.subject_id = s.id
                LEFT JOIN teachers t ON c.teacher_id = t.id
                LEFT JOIN semesters sem ON c.semester_id = sem.id
                LEFT JOIN schedules sch ON c.id = sch.class_id
                LEFT JOIN enrollments e ON c.id = e.class_id
                LEFT JOIN students st ON e.student_id = st.id
                WHERE c.id = ?
                ORDER BY 
                    CASE WHEN st.name IS NULL THEN 1 ELSE 0 END, -- Đưa dòng null xuống cuối
                    SUBSTRING_INDEX(st.name, ' ', -1) ASC, -- Sắp xếp theo TÊN (từ cuối cùng)
                    st.name ASC";

    $stmt_full = $pdo->prepare($sql_full);
    $stmt_full->execute([$class_id]);
    $data = $stmt_full->fetchAll();

    if (empty($data)) {
        $check_class = $pdo->prepare("SELECT id FROM classes WHERE id = ?");
        $check_class->execute([$class_id]);
        if (!$check_class->fetch()) {
            http_response_code(404);
            $response['message'] = 'Không tìm thấy lớp học này.';
            echo json_encode($response);
            exit();
        }
    }

    $response['success'] = true;
    $response['data'] = $data;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>