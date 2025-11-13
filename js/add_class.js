// SỬA: Đổi URL
const API_URL = 'backend/add_class.php';

document.addEventListener('DOMContentLoaded', function() 
{
    const form = document.getElementById('add_class_form');    
    const classId = document.getElementById('classId');
    const subjectId = document.getElementById('subjectId');
    const teacherId = document.getElementById('teacherId');
    const classRoom = document.getElementById('classRoom');
    const scheduleTime = document.getElementById('scheduleTime');
    const format = document.getElementById('format');
    const dayOfWeek = document.getElementById('dayOfWeek'); 
    const successMessage = document.getElementById('success-message');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = validateInputs();
        if (isValid) {
            form.querySelector('button[type="submit"]').disabled = true;
            
            const dataToSend = {
                data: {
                    class_id: classId.value.trim(),
                    subject_id: subjectId.value.trim(),
                    teacher_id: teacherId.value.trim(),
                    class_room: classRoom.value.trim(),
                    schedule_time: scheduleTime.value.trim(),
                    format: format.value.trim(),
                    day_of_week: dayOfWeek.value.trim()
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
            .then(({status, body }) => {
                if (body.success) {
                    successMessage.style.color = 'green';
                    successMessage.textContent = body.message;
                    form.reset();
                } else {
                    successMessage.style.color = 'red';
                    successMessage.textContent = body.message;
                }
            })
            .catch(error => {
                console.error('Lỗi Fetch:', error);
                successMessage.style.color = 'red';
                successMessage.textContent = 'Đã xảy ra lỗi kết nối. Vui lòng thử lại.';
            })
            .finally(() => {
                form.querySelector('button[type="submit"]').disabled = false;
            });
        }
    
    });
    
    function validateInputs() {
        resetErrors();
        let isValid = true;
        const classIdVal = classId.value.trim();
        const subjectIdVal = subjectId.value.trim();
        const teacherIdVal = teacherId.value.trim();
        const classRoomVal = classRoom.value.trim();
        const scheduleVal = scheduleTime.value.trim();
        const formatVal = format.value.trim();
        const dayOfWeekVal = dayOfWeek.value.trim(); 

        if (classIdVal === '') {
            setError(classId, 'Mã lớp không được để trống');
            isValid = false;
        }
        if (subjectIdVal === '') {
            setError(subjectId, 'ID Môn học không được để trống');
            isValid = false;
        }
        if (teacherIdVal === '') {
            setError(teacherId, 'ID Giảng viên không được để trống');
            isValid = false;
        }
        if (classRoomVal === '') {
            setError(classRoom, 'Phòng học không được để trống');
            isValid = false;
        }
        if (dayOfWeekVal === '') {
            setError(dayOfWeek, 'Vui lòng chọn ngày học');
            isValid = false;
        }
        if (scheduleVal === '') {
            setError(scheduleTime, 'Vui lòng chọn ca học');
            isValid = false;
        }
        if (formatVal === '') {
            setError(format, 'Vui lòng chọn hình thức học');
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
            if (successMessage) {
                successMessage.textContent = '';
            }
    }
    function setError(inputElement, message){
        if (inputElement) {
            inputElement.classList.add('is-invalid');
            const errorSpan = document.getElementById(`${inputElement.id}-error`);
            if (errorSpan) {
                errorSpan.textContent = message;
            }
        }
    }
});