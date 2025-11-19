<?php
require 'config.php';

try {
    $sql = "SELECT 
                c.id AS class_id, c.class_code, c.max_students, c.format,
                s.name AS subject_name,
                sem.name AS semester_name,
                CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS current_students
            FROM classes c
            LEFT JOIN subjects s ON c.subject_id = s.id
            LEFT JOIN teachers t ON c.teacher_id = t.id
            LEFT JOIN semesters sem ON c.semester_id = sem.id
            ORDER BY c.id DESC";

    $stmt = $pdo->query($sql);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>