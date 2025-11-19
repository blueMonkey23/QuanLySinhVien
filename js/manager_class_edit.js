// js/manager_class_edit.js

const API_FETCH_DROPDOWN = `${CONFIG.API_BASE_URL}class_data_fetch.php`;
const API_GET_CLASS = `${CONFIG.API_BASE_URL}manager_class_get.php`;
const API_EDIT_CLASS = `${CONFIG.API_BASE_URL}manager_class_edit.php`;

document.addEventListener('DOMContentLoaded', async function() {
    
    // 1. Lấy ID từ URL
    const params = new URLSearchParams(window.location.search);
    const classId = params.get('id');

    if (!classId) {
        alert('Không tìm thấy ID lớp học.');
        window.location.href = 'manager_classes.html';
        return;
    }

    // 2. Khai báo Elements
    const form = document.getElementById('edit_class_form');
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('success-message');

    // Các input cần validate
    const classCode = document.getElementById('class_code');
    const subjectId = document.getElementById('subject_id');
    const teacherId = document.getElementById('teacher_id');
    const classRoom = document.getElementById('class_room');
    const scheduleTime = document.getElementById('schedule_time');
    const format = document.getElementById('format');
    const dayOfWeek = document.getElementById('day_of_week');

    // --- A. HÀM TẢI DROPDOWN ---
    async function loadDropdowns() {
        try {
            const res = await fetch(API_FETCH_DROPDOWN);
            const data = await res.json();
            if (data.success) {
                populateSelect(subjectId, data.data.subjects, 'id', 'subject_code', 'name');
                populateSelect(teacherId, data.data.teachers, 'id', 'teacher_code', 'name');
            }
        } catch (e) { console.error(e); }
    }

    function populateSelect(selectElement, items, valKey, codeKey, nameKey) {
        selectElement.innerHTML = '<option value="">-- Chọn --</option>';
        items.forEach(i => {
            const text = i[codeKey] ? `(${i[codeKey]}) ${i[nameKey]}` : i[nameKey];
            const option = document.createElement('option');
            option.value = i[valKey];
            option.textContent = text;
            selectElement.appendChild(option);
        });
    }

    // --- B. HÀM TẢI DỮ LIỆU LỚP & ĐIỀN FORM ---
    async function loadClassData() {
        try {
            const res = await fetch(`${API_GET_CLASS}?id=${classId}`);
            const result = await res.json();
            
            if (!result.success) throw new Error(result.message);
            
            const d = result.data;
            
            // Điền dữ liệu vào ô input
            classCode.value = d.class_code;
            classRoom.value = d.room;
            format.value = d.format;
            
            // Chọn giá trị cho Dropdown (sau khi dropdown đã load xong)
            subjectId.value = d.subject_id;
            teacherId.value = d.teacher_id;

            // Xử lý ngày học (Map số -> chuỗi để khớp với <option value="Thứ Hai">)
            const dayMap = {2:'Thứ Hai', 3:'Thứ Ba', 4:'Thứ Tư', 5:'Thứ Năm', 6:'Thứ Sáu', 7:'Thứ Bảy', 8:'Chủ Nhật'};
            // Nếu API trả về số thì map, nếu trả về chuỗi thì giữ nguyên
            const dayStr = dayMap[d.day_of_week] || d.day_of_week; 
            dayOfWeek.value = dayStr;

            // Xử lý Ca học (Ghép Start-End)
            const start = d.start_time.substring(0, 5); 
            const end = d.end_time.substring(0, 5);
            scheduleTime.value = `${start}-${end}`;

        } catch (e) {
            alert('Lỗi tải dữ liệu: ' + e.message);
            console.error(e);
        }
    }

    // --- C. XỬ LÝ SUBMIT ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 1. VALIDATE TRƯỚC KHI GỬI
        if (!validateInputs()) {
            return; // Dừng lại nếu không hợp lệ
        }

        submitBtn.disabled = true;
        successMessage.textContent = '';

        const formData = {
            id: classId,
            data: {
                class_id: classCode.value.trim(),
                subject_id: subjectId.value,
                teacher_id: teacherId.value,
                class_room: classRoom.value.trim(),
                schedule_time: scheduleTime.value,
                format: format.value,
                day_of_week: dayOfWeek.value
            }
        };

        fetch(API_EDIT_CLASS, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
        })
        .then(r => r.json())
        .then(res => {
            submitBtn.disabled = false;
            if (res.success) {
                successMessage.textContent = res.message;
                // Chuyển trang sau 1.5s
                setTimeout(() => {
                    window.location.href = 'manager_classes.html';
                }, 1500);
            } else {
                alert('Lỗi: ' + res.message);
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            alert('Lỗi kết nối server');
            console.error(err);
        });
    });

    // --- D. HÀM VALIDATION (Giống hệt add_class.js) ---
    function validateInputs() {
        resetErrors();
        let isValid = true;

        if (classCode.value.trim() === '') { setError(classCode, 'Mã lớp không được để trống'); isValid = false; }
        if (subjectId.value === '') { setError(subjectId, 'Vui lòng chọn Môn học'); isValid = false; }
        if (teacherId.value === '') { setError(teacherId, 'Vui lòng chọn Giảng viên'); isValid = false; }
        if (classRoom.value.trim() === '') { setError(classRoom, 'Phòng học không được để trống'); isValid = false; }
        if (dayOfWeek.value === '') { setError(dayOfWeek, 'Vui lòng chọn ngày học'); isValid = false; }
        if (scheduleTime.value === '') { setError(scheduleTime, 'Vui lòng chọn ca học'); isValid = false; }
        if (format.value === '') { setError(format, 'Vui lòng chọn hình thức học'); isValid = false; }

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
            // Giả định ID của span lỗi là {inputID}-error
            const errorSpan = document.getElementById(`${inputElement.id}-error`);
            if (errorSpan) {
                errorSpan.textContent = message;
            }
        }
    }

    // --- KHỞI CHẠY ---
    // Chạy tuần tự: Tải Dropdown xong mới tải dữ liệu Class để fill vào
    await loadDropdowns(); 
    await loadClassData();
});