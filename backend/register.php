<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
$input = json_decode(file_get_contents('php://input'), true);
$data = $input['data'] ?? null;

try {
    // Check email trùng
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $chk->execute([$data['email']]);
    if ($chk->fetch()) throw new Exception("Email đã tồn tại");

    $pdo->beginTransaction();

    // Tạo User
    $passHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
    $stmtUser->execute([$data['name'], $data['email'], $passHash]);
    $userId = $pdo->lastInsertId();

    // Gán Role Student
    $pdo->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, 4)")->execute([$userId]);

    // Tạo Student
    $stmtStd = $pdo->prepare("INSERT INTO students (user_id, student_code, name, status) VALUES (?, ?, ?, 1)");
    $stmtStd->execute([$userId, $data['student_id'], $data['name']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Đăng ký thành công']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>