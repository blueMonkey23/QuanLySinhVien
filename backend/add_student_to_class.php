<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

$input = json_decode(file_get_contents('php://input'), true);
$cid = $input['class_id'] ?? null;
$code = $input['student_code'] ?? null;

try {
    // Tìm SV
    $st = $pdo->prepare("SELECT id, name FROM students WHERE student_code = ?");
    $st->execute([$code]);
    $student = $st->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Mã sinh viên không tồn tại']);
        exit;
    }

    // Check trùng
    $chk = $pdo->prepare("SELECT id FROM enrollments WHERE class_id=? AND student_id=?");
    $chk->execute([$cid, $student['id']]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Sinh viên này đã ở trong lớp rồi']);
        exit;
    }

    // Thêm
    $ins = $pdo->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)");
    $ins->execute([$cid, $student['id']]);
    
    echo json_encode(['success' => true, 'message' => "Đã thêm: " . $student['name']]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>