<?php
require 'config.php';

<<<<<<< Updated upstream
try {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT student_code, name FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();
        if ($student) {
            $response['success'] = true;
            $response['data'] = [
                'logged_in' => true,
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'], 
                'fullname' => $student['name'], 
                'studentId' => $student['student_code'] 
            ];
        } else {
             $response['success'] = true; 
             $response['data'] = ['logged_in' => false, 'message' => 'Lỗi: Không tìm thấy hồ sơ sinh viên.'];
        }

    } else {
        $response['success'] = true; 
        $response['data'] = ['logged_in' => false];
    }
} catch (PDOException $e) {
    $response['message'] = 'Lỗi Database khi kiểm tra trạng thái: ' . $e->getMessage();
=======
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'] ?? 'student';

    // Lấy tên user
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => [
            'logged_in' => true,
            'fullname' => $user['name'],
            'role' => $role,
            'identifier' => $role === 'admin' ? 'Quản trị viên' : $user['email']
        ]
    ]);
} else {
    echo json_encode(['success' => true, 'data' => ['logged_in' => false]]);
>>>>>>> Stashed changes
}
?>