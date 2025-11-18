// Đổi đường dẫn này cho đúng thư mục của bạn
const API_URL = 'http://localhost:8080/backend/add_class.php';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add_class_form');
    const successMessage = document.getElementById('success-message');

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        successMessage.textContent = '';
        
        // Lấy dữ liệu
        const formData = {
            class_id: document.getElementById('classId').value.trim(),
            subject_id: document.getElementById('subjectId').value.trim(), // Nhập mã môn: IT01
            teacher_id: document.getElementById('teacherId').value.trim(), // Nhập mã GV: GV01
            class_room: document.getElementById('classRoom').value.trim(),
            day_of_week: document.getElementById('dayOfWeek').value,
            schedule_time: document.getElementById('scheduleTime').value,
            format: document.getElementById('format').value
        };

        // Validate cơ bản (để trống)
        for (let key in formData) {
            if (!formData[key]) {
                alert('Vui lòng điền đầy đủ thông tin!');
                return;
            }
        }

        // Gửi API
        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: formData })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert(response.message);
                form.reset();
                window.location.href = 'manager_classes.html'; // Chuyển về danh sách lớp
            } else {
                alert(response.message); // Hiện thông báo lỗi chi tiết từ PHP
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối Server');
        });
    });
});