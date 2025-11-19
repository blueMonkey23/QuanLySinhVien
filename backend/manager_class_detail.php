<?php
require 'config.php';

$id = $_GET['id'] ?? 0;

try {
    // Info lớp
    $sql1 = "SELECT c.class_code, c.max_students, s.name as subject_name, 
             sem.name as semester_name, CONCAT(t.first_name,' ',t.last_name) as teacher_name,
             sch.day_of_week, sch.start_time, sch.end_time, sch.room,
             (SELECT COUNT(*) FROM enrollments WHERE class_id=c.id) as current_students
             FROM classes c
             LEFT JOIN subjects s ON c.subject_id=s.id
             LEFT JOIN teachers t ON c.teacher_id=t.id
             LEFT JOIN semesters sem ON c.semester_id=sem.id
             LEFT JOIN schedules sch ON c.id=sch.class_id
             WHERE c.id=?";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$id]);
    $info = $stmt1->fetch();

    // List SV
    $sql2 = "SELECT e.id as enrollment_id, st.student_code, st.name, e.midterm_score, e.final_score
             FROM enrollments e
             JOIN students st ON e.student_id = st.id
             WHERE e.class_id=?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$id]);
    $students = $stmt2->fetchAll();

    echo json_encode(['success' => true, 'data' => ['class_info' => $info, 'students' => $students]]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>