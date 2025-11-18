<?php
// Tệp: backend/class_data_fetch.php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận GET';
    echo json_encode($response);
    exit();
}

try {
    $data = [];

    // 1. Lấy danh sách Môn học
    $stmt_subjects = $pdo->prepare("SELECT id, name, subject_code FROM subjects ORDER BY name");
    $stmt_subjects->execute();
    $data['subjects'] = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    // 2. Lấy danh sách Giáo viên (SỬA LỖI Ở ĐÂY)
    // Ghép first_name và last_name lại thành 'name' để hiển thị
    $stmt_teachers = $pdo->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) as name, teacher_code 
        FROM teachers 
        ORDER BY first_name ASC
    ");
    $stmt_teachers->execute();
    $data['teachers'] = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['data'] = $data;

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>