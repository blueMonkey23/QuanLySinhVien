<?php
require 'config.php';

try {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // 1. Tìm vai trò của người dùng
        $stmt = $pdo->prepare("
            SELECT r.name AS role_name 
            FROM roles r
            JOIN role_user ru ON r.id = ru.role_id
            WHERE ru.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $role = $stmt->fetch();

        if (!$role) {
            $response['success'] = true; 
            $response['data'] = ['logged_in' => false, 'message' => 'Lỗi: Không tìm thấy vai trò.'];
            echo json_encode($response);
            exit();
        }

        $role_name = $role['role_name'];
        $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
        
        $data = [
            'logged_in' => true,
            'user_id' => $user_id,
            'email' => $user_email,
            'role' => $role_name
        ];

        // 2. Lấy thông tin chi tiết dựa trên vai trò
        if ($role_name === 'student') {
            $stmt = $pdo->prepare("SELECT student_code, name FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            if ($profile) {
                $data['fullname'] = $profile['name'];
                $data['identifier'] = $profile['student_code']; // Mã Sinh viên
            }
        } elseif ($role_name === 'teacher') {
            $stmt = $pdo->prepare("SELECT teacher_code, first_name, last_name FROM teachers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            if ($profile) {
                $data['fullname'] = $profile['first_name'] . ' ' . $profile['last_name'];
                $data['identifier'] = $profile['teacher_code']; // Mã Giáo viên
            }
        } elseif ($role_name === 'admin' || $role_name === 'manager') { // Thêm vai trò 'manager' nếu bạn dùng tên đó
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            if ($profile) {
                $data['fullname'] = $profile['name'];
                $data['identifier'] = 'Quản trị viên'; // Hoặc 'Quản lý'
                $data['email'] = $profile['email'];
            }
        }

        if (isset($data['fullname'])) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
             $response['success'] = true; 
             $response['data'] = ['logged_in' => false, 'message' => 'Lỗi: Không tìm thấy hồ sơ chi tiết.'];
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