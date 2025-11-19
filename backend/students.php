<?php
// backend/students.php
require 'config.php'; // Sử dụng file config chung của nhóm

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// --- API 1: LẤY DANH SÁCH & TÌM KIẾM ---
if ($method === 'GET' && $action === 'list') {
    $keyword = $_GET['keyword'] ?? '';
    // Tìm theo Tên hoặc Mã SV
    $sql = "SELECT s.id, s.student_code, s.name, s.dob, s.gender, s.address, s.status, u.email 
            FROM students s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.student_code LIKE ? OR s.name LIKE ? 
            ORDER BY s.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    $data = $stmt->fetchAll();

    // Chuyển đổi dữ liệu hiển thị
    $result = array_map(function($row){
        // Map giới tính từ DB (male/female) sang hiển thị (Nam/Nữ)
        $genderShow = 'Khác';
        if ($row['gender'] == 'male') $genderShow = 'Nam';
        if ($row['gender'] == 'female') $genderShow = 'Nữ';
        
        $row['gender_text'] = $genderShow;
        $row['fullname'] = $row['name']; // Đồng bộ tên trường cho JS dễ gọi
        return $row;
    }, $data);

    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}

// --- API 2: THÊM, SỬA, KHÓA (POST) ---
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Hàm chuyển đổi giới tính sang tiếng Anh để lưu DB
    $mapGenderToDB = function($g) {
        if ($g === 'Nam') return 'male';
        if ($g === 'Nữ') return 'female';
        return 'other';
    };

    // 2.1 THÊM MỚI SINH VIÊN
    if ($action === 'create') {
        try {
            $pdo->beginTransaction();
            
            // B1: Tạo tài khoản User (Mật khẩu mặc định là Mã SV)
            $passHash = password_hash($input['student_code'], PASSWORD_DEFAULT);
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmtUser->execute([$input['fullname'], $input['email'], $passHash]);
            $uid = $pdo->lastInsertId();
            
            // B2: Gán quyền Student
            // (Giả sử role_id của student là 4, bạn cần check lại bảng roles của Chiến)
            $pdo->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, 4)")->execute([$uid]);

            // B3: Tạo thông tin Student
            $sql = "INSERT INTO students (user_id, student_code, name, dob, gender, address, status) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $uid, 
                $input['student_code'], 
                $input['fullname'], 
                $input['dob'], 
                $mapGenderToDB($input['gender']), 
                $input['address']
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Thêm sinh viên thành công']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    // 2.2 CẬP NHẬT THÔNG TIN
    elseif ($action === 'update') {
        try {
            $sql = "UPDATE students SET name=?, dob=?, gender=?, address=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['fullname'], 
                $input['dob'], 
                $mapGenderToDB($input['gender']), 
                $input['address'], 
                $input['id']
            ]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    // 2.3 KHÓA / MỞ KHÓA HỒ SƠ
    elseif ($action === 'toggle_lock') {
        try {
            $pdo->prepare("UPDATE students SET status = NOT status WHERE id=?")->execute([$input['id']]);
            echo json_encode(['success' => true, 'message' => 'Đổi trạng thái thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }
}
?>