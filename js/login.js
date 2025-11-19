// js/login.js
const API_URL = 'http://localhost:8080/backend/login.php';

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login_form');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const errorMsg = document.getElementById('success-message'); // Tận dụng thẻ này báo lỗi

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        errorMsg.textContent = '';
        errorMsg.style.color = 'red';

        const data = {
            email: email.value.trim(),
            password: password.value.trim()
        };

        if (!data.email || !data.password) {
            errorMsg.textContent = 'Vui lòng nhập đầy đủ thông tin';
            return;
        }

        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: data })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                errorMsg.style.color = 'green';
                errorMsg.textContent = 'Đăng nhập thành công! Đang chuyển hướng...';
                
                // Chuyển hướng dựa trên quyền (nếu cần), tạm thời về dashboard
                setTimeout(() => {
                    window.location.href = 'manager_dashboard.html';
                }, 1000);
            } else {
                errorMsg.textContent = result.message;
            }
        })
        .catch(err => {
            console.error(err);
            errorMsg.textContent = 'Lỗi kết nối Server';
        });
    });
});