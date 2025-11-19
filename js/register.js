// js/register.js
const API_URL = 'http://localhost:8080/backend/register.php';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('register_form');
    const msg = document.getElementById('success-message');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        msg.textContent = '';
        
        const data = {
            name: document.getElementById('fullname').value.trim(),
            email: document.getElementById('email').value.trim(),
            student_id: document.getElementById('student_id').value.trim(),
            password: document.getElementById('password').value.trim(),
            confirm: document.getElementById('confirm_password').value.trim()
        };

        if (data.password !== data.confirm) {
            msg.style.color = 'red';
            msg.textContent = 'Mật khẩu nhập lại không khớp';
            return;
        }

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: data })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                msg.style.color = 'green';
                msg.textContent = 'Đăng ký thành công! Chuyển sang đăng nhập...';
                setTimeout(() => window.location.href = 'login.html', 2000);
            } else {
                msg.style.color = 'red';
                msg.textContent = res.message;
            }
        })
        .catch(() => {
            msg.style.color = 'red';
            msg.textContent = 'Lỗi kết nối Server';
        });
    });
});