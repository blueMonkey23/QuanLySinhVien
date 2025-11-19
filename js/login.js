document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login_form');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const API_URL = `${CONFIG.API_BASE_URL}/login.php`;
    //Gắn sự kiện "submit" cho form
    form.addEventListener('submit', function(event) {
        // Ngăn form tải lại trang
        event.preventDefault();
        //Chạy hàm xử lý đăng nhập
        handleLogin();
    });

    //Hàm xử lý logic đăng nhập
    // Thay thế hàm handleLogin trong tệp js/login.js

    //Hàm xử lý logic đăng nhập
    async function handleLogin() {
        resetErrors();
        const emailVal = email.value.trim();
        const passwordVal = password.value.trim();
        let isValid = true;

        //Kiểm tra (validate) cơ bản
        if (emailVal === '') {
            setError(email, 'Email không được để trống');
            isValid = false;
        }

        if (passwordVal === '') {
            setError(password, 'Mật khẩu không được để trống');
            isValid = false;
        }
        const data = {
            email: emailVal,
            password: passwordVal
        };
        //Nếu cả 2 trường đều đã được điền
        if (isValid) {
            const successMessage = document.getElementById('success-message')
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },  
                    body: JSON.stringify({action: 'login', data: data})
                }
                );
                const result = await response.json();
                
                if (result.success) {
                    successMessage.textContent = result.message;
                    
                    // --- BẮT ĐẦU LOGIC CHUYỂN HƯỚNG THEO VAI TRÒ (ROLE-BASED REDIRECTION) ---
                    const userRole = result.data.role;
                    let redirectUrl = 'index.html'; // Mặc định cho sinh viên
                    
                    // Nếu là admin, manager, hoặc teacher, chuyển đến dashboard quản lý
                    if (userRole === 'admin' || userRole === 'manager' || userRole === 'teacher') {
                        redirectUrl = 'manager_dashboard.html';
                    }

                    setTimeout(() => {
                        window.location.href = redirectUrl; // Chuyển hướng
                    }, 2000);
                    // ------------------ KẾT THÚC LOGIC CHUYỂN HƯỚNG --------------------
                    
                } else {
                    setError(email, '');
                    setError(password, 'Email hoặc mật khẩu không chính xác.');
                }
            } catch (error) {
                alert('Đã có lỗi xảy ra trong quá trình đăng nhập. Vui lòng thử lại sau.');
                console.error('Error during login:', error);
            }

        }
    }

    async function setError(inputElement, message) {
        inputElement.classList.add('is-invalid');
        const errorSpan = document.getElementById(`${inputElement.id}-error`);
        if (errorSpan) {
            errorSpan.textContent = message;
        }
    }

    function resetErrors() {
        const invalidInputs = document.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => input.classList.remove('is-invalid'));

        const errorSpans = document.querySelectorAll('.error-message');
        errorSpans.forEach(span => span.textContent = '');
    }

});