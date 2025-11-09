<?php
require 'config.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405); 
    $response['message'] = 'Phương thức không hợp lệ, chỉ chấp nhận POST';
    echo json_encode($response);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['data']['email'] ?? '';
$name = $input['data']['name'] ?? '';
$student_code = $input['data']['student_id'] ?? ''; 
$password = $input['data']['password'] ?? '';
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409); 
        $response['message'] = 'Email này đã được đăng ký!';
        echo json_encode($response);
        exit();
    }
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_code = ?");
    $stmt->execute([$student_code]);
    if ($stmt->fetch()) {
        http_response_code(409); 
        $response['message'] = 'Mã sinh viên này đã được đăng ký';
        echo json_encode($response);
        exit();
    }
    // dùng transaction để đảm bảo cả 2 lệnh được thực thi thành công
    $pdo->beginTransaction();
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt_user = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $stmt_user->execute([$name, $email, $password_hash]);
    $new_user_id = $pdo->lastInsertId();
    $stmt_student = $pdo->prepare("INSERT INTO students (user_id, student_code, name) VALUES (?, ?, ?)");
    $stmt_student->execute([$new_user_id, $student_code, $name]);
    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'Đăng ký thành công!';

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500); 
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>