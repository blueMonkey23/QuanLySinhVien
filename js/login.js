document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('login_form');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    //Gắn sự kiện "submit" cho form
    form.addEventListener('submit', function(event) {
        // Ngăn form tải lại trang
        event.preventDefault();
        //Chạy hàm xử lý đăng nhập
        handleLogin();
    });

    //Hàm xử lý logic đăng nhập
    function handleLogin() {
        //Xóa mọi lỗi cũ
        resetErrors();
        
        //Lấy giá trị từ input
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

        //Nếu cả 2 trường đều đã được điền
        if (isValid) {
            const successMessage = document.getElementById('success-message')
            // Lấy danh sách users từ localStorage
            const users = JSON.parse(localStorage.getItem('users')) || [];

            //Tìm người dùng khớp cả email VÀ password
            const foundUser = users.find(user => {
                return user.email === emailVal && user.password === passwordVal;
            });

            if (foundUser) {
                //ĐĂNG NHẬP THÀNH CÔNG
                //Lưu thông tin người dùng hiện tại vào localStorage
                localStorage.setItem('currentUser', JSON.stringify(foundUser));
                successMessage.textContent = 'Đăng nhập thành công!';
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 2000);

            } else {
                //ĐĂNG NHẬP THẤT BẠI
                setError(password, 'Email hoặc mật khẩu không chính xác.');
            }
        }
    }

    function setError(inputElement, message) {
        inputElement.classList.add('is-invalid'); // Thêm class viền đỏ của Bootstrap
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