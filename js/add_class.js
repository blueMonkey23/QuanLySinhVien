// js/add_class.js
const API_URL = 'http://localhost:8080/backend/add_class.php';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add_class_form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = {
            class_id: document.getElementById('classId').value.trim(),
            subject_id: document.getElementById('subjectId').value.trim(),
            teacher_id: document.getElementById('teacherId').value.trim(),
            class_room: document.getElementById('classRoom').value.trim(),
            day_of_week: document.getElementById('dayOfWeek').value,
            schedule_time: document.getElementById('scheduleTime').value,
            format: document.getElementById('format').value
        };

        fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({data: data})
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                alert(res.message);
                window.location.href = 'manager_classes.html';
            } else {
                alert(res.message);
            }
        })
        .catch(() => alert('Lỗi Server'));
    });
});