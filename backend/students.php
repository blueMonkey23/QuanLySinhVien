<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    $keyword = $_GET['keyword'] ?? '';
    $sql = "SELECT s.id, s.student_code, s.name, s.dob, s.gender, s.address, s.status, u.email 
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.student_code LIKE ? OR s.name LIKE ?
            ORDER BY s.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    $students = $stmt->fetchAll();

    $result = array_map(function($std) {
        return [
            'id' => $std['id'],
            'student_code' => $std['student_code'],
            'fullname' => $std['name'],
            'email' => $std['email'],
            'dob' => $std['dob'],
            'gender' => ($std['gender'] === 'male') ? 'Nam' : (($std['gender'] === 'female') ? 'Nữ' : 'Khác'),
            'address' => $std['address'],
            'status' => $std['status']
        ];
    }, $students);

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $mapGender = function($g) {
        if ($g === 'Nam') return 'male';
        if ($g === 'Nữ') return 'female';
        return 'other';
    };

    if ($action === 'create') {
        $pdo->beginTransaction();
        try {
            $passHash = password_hash($input['student_code'], PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmtUser->execute([$input['fullname'], $input['email'], $passHash]);
            $userId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, 4)")->execute([$userId]);

            $stmtStd = $pdo->prepare("INSERT INTO students (user_id, student_code, name, dob, gender, address, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmtStd->execute([$userId, $input['student_code'], $input['fullname'], $input['dob'], $mapGender($input['gender']), $input['address']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Thêm thành công!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'update') {
        try {
            $sql = "UPDATE students SET name=?, dob=?, gender=?, address=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['fullname'], $input['dob'], $mapGender($input['gender']), $input['address'], $input['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'toggle_lock') {
        $stmt = $pdo->prepare("UPDATE students SET status = NOT status WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Đã thay đổi trạng thái!']);
    }
}
?>