<?php
require 'config.php';
$input = json_decode(file_get_contents('php://input'), true);
$grades = $input['grades'] ?? [];

if (empty($grades)) {
    echo json_encode(['success' => false, 'message' => 'Không có dữ liệu điểm']);
    exit;
}

try {
    $pdo->beginTransaction();
    $sql = "UPDATE enrollments SET midterm_score = ?, final_score = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    foreach ($grades as $g) {
        $stmt->execute([$g['midterm_score'], $g['final_score'], $g['enrollment_id']]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Đã lưu điểm thành công']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>