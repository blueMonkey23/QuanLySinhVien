<?php
require 'config.php';

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
}

echo json_encode($response);
?>