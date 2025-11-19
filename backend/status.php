<?php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logged_in' => true,
            'fullname' => $user['name'],
            'role' => $_SESSION['role'] ?? 'student'
        ]
    ]);
} else {
    echo json_encode(['success' => true, 'data' => ['logged_in' => false]]);
}
?>