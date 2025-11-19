<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 1. LẤY DANH SÁCH
if ($method === 'GET' && $action === 'list') {
    $keyword = $_GET['keyword'] ?? '';
    $sql = "SELECT s.*, u.email 
            FROM students s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.student_code LIKE ? OR s.name LIKE ? 
            ORDER BY s.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    $data = $stmt->fetchAll();
    
    // Map dữ liệu để Frontend dễ dùng (fullname -> name)
    $result = array_map(function($row){
        $row['fullname'] = $row['name']; 
        return $row;
    }, $data);

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// 2. THAO TÁC POST
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $mapGender = function($g) {
        if ($g == 'Nam') return 'male';
        if ($g == 'Nữ') return 'female';
        return 'other';
    };

    if ($action === 'create') {
        try {
            $pdo->beginTransaction();
            // Tạo User ảo để login
            $passHash = password_hash($input['student_code'], PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmtUser->execute([$input['fullname'], $input['email'], $passHash]);
            $uid = $pdo->lastInsertId();
            
            // Gán role
            $pdo->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, 4)")->execute([$uid]);

            // Tạo Student
            $sql = "INSERT INTO students (user_id, student_code, name, dob, gender, address, status) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid, $input['student_code'], $input['fullname'], $input['dob'], $mapGender($input['gender']), $input['address']]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Thêm thành công']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'update') {
        $sql = "UPDATE students SET name=?, dob=?, gender=?, address=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$input['fullname'], $input['dob'], $mapGender($input['gender']), $input['address'], $input['id']]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
    }
    elseif ($action === 'toggle_lock') {
        $pdo->prepare("UPDATE students SET status = NOT status WHERE id=?")->execute([$input['id']]);
        echo json_encode(['success' => true, 'message' => 'Đổi trạng thái thành công']);
    }
}
?>