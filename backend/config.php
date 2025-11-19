<?php
// Tắt hiển thị lỗi ra màn hình HTML để tránh hỏng JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// === CẤU HÌNH CORS (Cho phép Frontend cổng 8080 truy cập) ===
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// Xử lý request Preflight (Trình duyệt hỏi trước khi gửi dữ liệu)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();

// Thông tin Database
$host = '127.0.0.1';
$db   = 'quanlysinhvien_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối Database: ' . $e->getMessage()]);
    exit;
}
?>