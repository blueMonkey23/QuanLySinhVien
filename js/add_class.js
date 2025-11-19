// Tệp: js/add_class.js

// SỬA ĐƯỜNG DẪN: Sử dụng đường dẫn tương đối để an toàn hơn
const API_ADD_CLASS = `${CONFIG.API_BASE_URL}/manager_class_add.php`;
const API_FETCH_DATA = `${CONFIG.API_BASE_URL}/class_data_fetch.php`;

document.addEventListener('DOMContentLoaded', function() 
{
    const form = document.getElementById('add_class_form');    
    const classCode = document.getElementById('class_code'); 
    const subjectId = document.getElementById('subject_id');
    const teacherId = document.getElementById('teacher_id');
    const classRoom = document.getElementById('class_room'); 
    const scheduleTime = document.getElementById('schedule_time');
    const format = document.getElementById('format');
    const dayOfWeek = document.getElementById('day_of_week'); 
    const successMessage = document.getElementById('success-message');
    const submitBtn = document.getElementById('submitBtn');

    // ==========================================================
    // 1. HÀM TẢI DỮ LIỆU ĐỘNG (SUBJECTS & TEACHERS)
    // ==========================================================
    async function loadClassData() {
        try {
            const response = await fetch(API_FETCH_DATA);
            
            // Kiểm tra nếu phản hồi không phải JSON hoặc bị lỗi 500
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();

            if (result.success && result.data) {
                // Điền dữ liệu Môn học (Hiển thị: (Mã) Tên môn)
                populateDropdown(subjectId, result.data.subjects, 'id', ['subject_code', 'name'], '-- Chọn Môn học --');
                
                // Điền dữ liệu Giáo viên (Hiển thị: (Mã) Tên GV)
                populateDropdown(teacherId, result.data.teachers, 'id', ['teacher_code', 'name'], '-- Chọn Giáo viên --');
            } else {
                console.error("Lỗi backend:", result.message);
                alert('Không thể tải danh sách: ' + result.message);
            }
        } catch (error) {
            console.error('Lỗi fetch dữ liệu lớp học:', error);
            // Không alert làm phiền nếu chỉ là lỗi nhỏ, nhưng log ra console để debug
        }
    }

    // Hàm chung để điền dữ liệu vào dropdown
    function populateDropdown(selectElement, dataArray, valueKey, textKeys, defaultText) {
        selectElement.innerHTML = `<option value="">${defaultText}</option>`;
        dataArray.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            
            // Xây dựng text hiển thị: Ví dụ "(MATH) Toán rời rạc" hoặc "(GV01) Nguyễn Văn A"
            let text = '';
            if (textKeys.length > 1 && item[textKeys[0]]) {
                text = `(${item[textKeys[0]]}) ${item[textKeys[1]]}`;
            } else if (textKeys.length === 1) {
                text = item[textKeys[0]];
            } else {
                text = "Unknown";
            }

            option.textContent = text;
            selectElement.appendChild(option);
        });
    }

    // ... (Phần code xử lý submit và validate bên dưới GIỮ NGUYÊN như trước) ...
    // (Chỉ cần copy đoạn code submit từ câu trả lời trước vào đây)
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = validateInputs();
        if (isValid) {
            submitBtn.disabled = true;
            successMessage.textContent = ''; 
            
            const dataToSend = {
                data: {
                    class_id: classCode.value.trim(),
                    subject_id: subjectId.value.trim(), // Value ở đây là ID môn học (int)
                    teacher_id: teacherId.value.trim(), // Value ở đây là ID giáo viên (int)
                    class_room: classRoom.value.trim(),
                    schedule_time: scheduleTime.value.trim(),
                    format: format.value.trim(),
                    day_of_week: dayOfWeek.value.trim()
                }
            }
            
            fetch(API_ADD_CLASS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            })
            .then(response => response.json())
            .then(result => {
                submitBtn.disabled = false;
                if (result.success) {
                    successMessage.textContent = result.message;
                    form.reset(); 
                    setTimeout(() => {
                        window.location.href = 'manager_classes.html';
                    }, 1500); 
                } else {
                    alert('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                console.error('Lỗi khi thêm lớp:', error);
                alert('Lỗi kết nối hoặc lỗi server.');
            });
        }
    });

    function validateInputs() {
        // ... (Giữ nguyên logic validate như cũ) ...
        // Code rút gọn để tiết kiệm không gian hiển thị, logic không đổi
        resetErrors();
        let isValid = true;
        if (classCode.value.trim() === '') { setError(classCode, 'Nhập mã lớp'); isValid = false; }
        if (subjectId.value === '') { setError(subjectId, 'Chọn môn học'); isValid = false; }
        if (teacherId.value === '') { setError(teacherId, 'Chọn giáo viên'); isValid = false; }
        if (classRoom.value.trim() === '') { setError(classRoom, 'Nhập phòng học'); isValid = false; }
        return isValid;
    }
    
    function resetErrors() {
        const invalidInputs = document.querySelectorAll('.is-invalid');
        invalidInputs.forEach(input => input.classList.remove('is-invalid'));
        const errorSpans = document.querySelectorAll('.error-message');
        errorSpans.forEach(span => span.textContent = '');
    }
    
    function setError(inputElement, message){
        if (inputElement) {
            inputElement.classList.add('is-invalid');
            const errorSpan = document.getElementById(`${inputElement.id}-error`);
            if (errorSpan) errorSpan.textContent = message;
        }
    }

    // Khởi chạy
    loadClassData();
});