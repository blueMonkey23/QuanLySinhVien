<?php
//Bật báo lỗi
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("access-control-allow-origin: http://localhost");
header("access-control-allow-methods: GET, POST, OPTIONS, PUT, DELETE");
header("access-control-allow-headers: Content-Type");
header("content-type: application/json; charset=utf-8");

//Cấu hình Database
$host = '127.0.0.1';
$db_name = 'quanlysinhvien_db';
$username = 'root';
$db_password = '';
$charset = 'utf8mb4';

//$response mặc định
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

//Tạo kết nối PDO
try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $db_password, $options);

} catch (PDOException $e) {
    http_response_code(500); 
    $response['message'] = "Lỗi Database: " . $e->getMessage();
    echo json_encode($response);
    exit(); 
}

function get_current_user_id() { 
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: Bạn chưa đăng nhập hoặc phiên đã hết hạn.',
        ]);
        exit();
    }
    return $_SESSION['user_id'];
}
?>