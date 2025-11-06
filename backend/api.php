<?php
error_log("--- FILE API.PHP ĐÃ CHẠY ---");
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("access-control-allow-origin: http://localhost");
header("access-control-allow-methods: GET, POST, OPTIONS, PUT, DELETE");
header("access-control-allow-headers: Content-Type");
header("content-type: application/json; charset=utf-8");
$host = '127.0.0.1';
$db_name = 'quanlysinhvien_db';
$username = 'root';
$db_password = '';
$charset = 'utf8mb4';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

function get_current_user_id() { 
    if (!isset($_SESSION['user_id'])) {
        https_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: Bạn chưa đăng nhập hoặc phiên đã hết hạn.',
        ]);
        exit();
    }
    return $_SESSION['user_id'];
}
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$action = $input['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD']; // Lấy phương thức HTTP của yêu cầu

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $username, $db_password, $options);
    error_log("API DEBUG: Method là: " . $method . " | Action là: " . $action);
    if ($method === 'POST' && $action === 'login'){
        

        $stmt = $pdo -> prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
        $stmt ->execute([$email]);
        $user = $stmt -> fetch();
        if (!$user) {
            error_log("API DEBUG: Không tìm thấy user với email: " . $email);
        } else {
            error_log("API DEBUG: Password nhận được (từ input): '" . $password . "'");
            error_log("API DEBUG: Hash lấy từ CSDL: '" . $user['password_hash'] . "'");
            
            // Kiểm tra kết quả verify
            $is_correct = password_verify($password, $user['password_hash']);
            error_log("API DEBUG: Kết quả password_verify: " . ($is_correct ? 'TRUE' : 'FALSE'));
        }
        if ($user && password_verify($password, $user['password_hash'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            $response['success'] = true;
            $response['message'] = 'Đăng nhập thành công!';
            $response['data'] = [
                'user_id' => $user['id'],
                'email' => $user['email']
            ];
        } else {
            http_response_code(401); // Unauthorized
            $response['message'] = 'Email hoặc mật khẩu không chính xác!';
        }
    } else if ($method === 'GET' && $action === 'logout'){
        session_unset();
        session_destroy();
        $response['success'] = true;
        $response['message'] = 'Đăng xuất thành công!';
    } else if ($method === 'GET' && $action === 'status'){
        if (isset($_SESSION['user_id'])){
            $response['success'] = true;
            $response['data'] = [
                'logged_in' => true,
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email']
            ];
        } else {
            $response['data'] = ['logged_in' => false];
        }
    }
        
} catch (PDOException $e) {
    http_response_code(500); // Lỗi server
    $response['message'] = "Lỗi Database: " . $e->getMessage();
}

echo json_encode($response);
?>