const validationPatterns = {
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    phone: /^(\+?84|0)?([3|5|7|8|9])+([0-9]{10})$/,
    namePattern: /^[a-zA-ZÀ-ỹ\s]+$/,
    studentIdPattern: /^[a-zA-Z0-9]{12}$/
};
const API_URL = `${CONFIG.API_BASE_URL}/register.php`; 
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
            //tránh spam
            form.querySelector('button[type="submit"]').disabled = true;
            const dataToSend = {
                data: {
                    name: fullname.value.trim(),
                    email: email.value.trim(),
                    student_id: studentId.value.trim(),
                    password: password.value.trim()
                }
            }

            fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dataToSend)
            })
            .then(response => {
                return response.json().then(data => ({
                    status: response.status,
                    body: data
                }));
            })
            .then (({status, body }) => {
                if (body.success) {
                    successMessage.style.color = 'green';
                    successMessage.textContent = body.message;
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    successMessage.style.color = 'red';
                    if (status === 409) { // lỗi đã tồn tại email hoặc mã sv
                        if (body.message.includes('email')) {
                            setError(email, body.message);
                        } else if (body.message.includes('student_id')) {
                            setError(studentId, body.message);
                        } else {
                        successMessage.textContent = body.message;
                    } 
                    } else {
                        // 500,405 
                        successMessage.textContent = body.message;
                    }
                    form.querySelector('button[type="submit"]').disabled = false;
                }
            })
            .catch(error => {
                console.error('lỗi fetch:', error);
                successMessage.style.color = 'red';
                successMessage.textContent = 'Lỗi kết nối. vui lòng thử lại sau!';
                form.querySelector('button[type="submit"]').disabled = false;

            })
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
        if (!fullnameVal) {
            setError(fullname, "Họ và tên không được để trống");
            isValid = false;
        }
        if (!emailVal) {
            setError(email, "Email không được để trống");
            isValid = false;
        }
        if (!studentIdVal) {
            setError(studentId, "Mã sinh viên không được để trống");
            isValid = false;
        }
        if (!passwordVal) {
            setError(password, "Mật khẩu không được để trống");
            isValid = false;
        }
        if (fullnameVal && !validationPatterns.namePattern.test(fullnameVal)) {
            setError(fullname, "Họ và tên chỉ được chứa chữ cái và khoảng trắng");
            isValid = false;
        }
        if (emailVal && !validationPatterns.email.test(emailVal)) {
            setError(email, 'Email không đúng định dạng');
            isValid = false;
        }
        if (studentIdVal && !validationPatterns.studentIdPattern.test(studentIdVal)) {
            setError(studentId, 'Mã sinh viên chỉ được chứa chữ và số và gồm 12 ký tự');
            isValid = false;
        }
        if (passwordVal && passwordVal.length < 6) {
            setError(password, "Mật khẩu phải có ít nhất 6 ký tự");
            isValid = false;
        }
        if (passwordVal.length >= 6 && !confirm_passwordVal) {
            setError(confirmPassword, "Bạn phải xác nhận mật khẩu");
            isValid = false;
        }
        if (confirm_passwordVal && passwordVal !== confirm_passwordVal) {
            setError(confirmPassword, 'Mật khẩu không khớp');
            isValid = false;
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