const validationPatterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    phone: /^(\+?84|0)?([3|5|7|8|9])+([0-9]{10})$/,
    namePattern: /^[a-zA-ZÀ-ỹ\s]+$/,
    studentIdPattern: /^[a-zA-Z0-9]{12}$/
};

document.addEventListener('DOMContentLoaded', function()
{
    const form = document.getElementById('register_form');
    const fullname = document.getElementById('fullname');
    const email = document.getElementById('email');
    const studentId = document.getElementById('student_id');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const successMessage = document.getElementById('success-message');
    form.addEventListener('submit', function(event) {
        event.preventDefault(); 
        let isValid = validateInputs();
        if (isValid) {
            // Lấy danh sách người dùng đã đăng ký từ localStorage
            let users = JSON.parse(localStorage.getItem('users')) || [];

            // Tạo đối tượng người dùng mới
            const newUser = {
                fullname: fullname.value.trim(),
                email: email.value.trim(),
                studentId: studentId.value.trim(),
                password: password.value.trim() // Chỉ lưu mật khẩu, không lưu "confirm_password"
            };

            // Thêm người dùng mới vào mảng
            users.push(newUser);

            // Lưu mảng users mới cập nhật trở lại localStorage
            localStorage.setItem('users', JSON.stringify(users));

            // Thông báo và chuyển hướng
            successMessage.textContent = 'Đăng ký thành công!';
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        }
    });


    function validateInputs() {
        resetErrors();
        let isValid = true;
        const fullnameVal = fullname.value.trim();
        const emailVal = email.value.trim();
        const studentIdVal = studentId.value.trim();
        const passwordVal = password.value.trim();
        const confirm_passwordVal = confirmPassword.value.trim();

        
        if(!validationPatterns.namePattern.test(fullnameVal))
        {
            setError(fullname, "Họ và tên chỉ được chứa chữ cái và khoảng trắng")
        }

        if(!validationPatterns.email.test(emailVal))
        {
            setError(email, 'Email không đúng định dạng');
            isValid=false;
        }
        if(!validationPatterns.studentIdPattern.test(studentIdVal))
        {
            setError(studentId, 'Mã sinh viên chỉ được chứa chữ và số và gồm 12 ký tự');
            isValid=false;
        }
        if (passwordVal.length < 6) {
            setError(password, "Mật khẩu phải có ít nhất 6 ký tự");
            isValid = false;
        }
        setError(fullname, validateEmpty(fullnameVal, "Họ và tên"));
        setError(email, validateEmpty(emailVal, "Email"));
        setError(studentId, validateEmpty(studentIdVal, "Mã sinh viên"));
        setError(password, validateEmpty(passwordVal, "Mật khẩu"));
        if (passwordVal.length >= 6) {
        setError(confirmPassword, validateEmpty(confirm_passwordVal, "Mật khẩu"));
        }
        if((confirm_passwordVal !== passwordVal) && passwordVal.length >= 6)
        {
            setError(confirmPassword, 'Mật khẩu không khớp');
            isValid=false;
        }
        
        if (isValid) {
            let users = JSON.parse(localStorage.getItem('users')) || [];
            if (users.some(user => user.email === emailVal)) {
                setError(email, 'Email này đã được sử dụng');
                isValid = false;
            }
            if (users.some(user => user.studentId === studentIdVal)) {
                setError(studentId, 'Mã sinh viên này đã được sử dụng');
                isValid = false;
            }
        }

        return isValid;

    }
    function resetErrors() 
    {
            const invalidInputs = document.querySelectorAll('.is-invalid');
            invalidInputs.forEach(input => input.classList.remove('is-invalid'));
            const errorSpans = document.querySelectorAll('.error-message');
            errorSpans.forEach(span => span.textContent = '');
    }

    function validateEmpty(value, fieldName) {
        if (!value || value.trim() === '') {
            isValid=false;
            return `${fieldName} không được để trống`;
        }
        return '';
    }

    function setError(inputElement, message){
        inputElement.classList.add('is-invalid');
        const errorSpan = document.getElementById(`${inputElement.id}-error`);
        if (errorSpan) {
            errorSpan.textContent = message;
        }
    }
});