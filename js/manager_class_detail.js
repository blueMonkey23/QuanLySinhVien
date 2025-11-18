//js/manager_class_detail.js
document.addEventListener('DOMContentLoaded', function() {

    // Định nghĩa các URL API
    const API_BASE_URL = 'http://localhost:8080/backend/';
// Các dòng dưới giữ nguyên
    const API_GET_DETAIL_URL = API_BASE_URL + 'manager_class_detail.php';
    const API_UPDATE_GRADES_URL = API_BASE_URL + 'manager_update_grades.php'; // Chúng ta sẽ tạo tệp này sau

    // Lấy các element trên trang
    const pageTitle = document.getElementById('page-title');
    const classCodeSubtitle = document.getElementById('class-code-subtitle');
    const classInfoContainer = document.getElementById('class-info-container');
    const studentListTbody = document.getElementById('student-list-tbody');
    const studentListTitle = document.getElementById('student-list-title');
    const saveAllGradesBtn = document.getElementById('save-all-grades-btn');

    // Hàm trợ giúp chuyển đổi số ngày (trong DB) sang chữ
    const getDayOfWeek = (dayNumber) => {
        const days = { 2: 'Thứ Hai', 3: 'Thứ Ba', 4: 'Thứ Tư', 5: 'Thứ Năm', 6: 'Thứ Sáu', 7: 'Thứ Bảy', 8: 'Chủ Nhật' };
        return days[dayNumber] || 'N/A';
    }

    // Hàm trợ giúp định dạng thời gian
    const formatTime = (timeString) => {
        if (!timeString) return 'N/A';
        const parts = timeString.split(':');
        return `${parts[0]}:${parts[1]}`;
    }

    // 1. LẤY ID LỚP HỌC TỪ URL
    const params = new URLSearchParams(window.location.search);
    const classId = params.get('id');

    if (!classId) {
        pageTitle.textContent = 'Lỗi';
        classInfoContainer.innerHTML = '<div class="alert alert-danger">Không tìm thấy ID lớp học trong URL.</div>';
        return;
    }

    // 2. GỌI API ĐỂ LẤY DỮ LIỆU
    fetch(`${API_GET_DETAIL_URL}?id=${classId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                const { class_info, students } = result.data;
                
                // 3. ĐIỀN THÔNG TIN LỚP HỌC
                populateClassInfo(class_info);
                
                // 4. ĐIỀN DANH SÁCH SINH VIÊN
                populateStudentList(students);
                
            } else {
                throw new Error(result.message || 'Không thể tải dữ liệu');
            }
        })
        .catch(error => {
            console.error('Lỗi khi tải chi tiết lớp học:', error);
            pageTitle.textContent = 'Lỗi Tải Dữ Liệu';
            classInfoContainer.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            studentListTbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${error.message}</td></tr>`;
        });

    // Hàm điền thông tin chi tiết lớp học
    function populateClassInfo(info) {
        pageTitle.textContent = `Chi tiết Lớp: ${info.subject_name}`;
        classCodeSubtitle.textContent = `Mã lớp: ${info.class_code}`;
        
        classInfoContainer.innerHTML = `
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Môn học</div>
                <div class="fw-semibold">${info.subject_name}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Giáo viên</div>
                <div class="fw-semibold">${info.teacher_name || 'Chưa gán'}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Học kỳ</div>
                <div class="fw-semibold">${info.semester_name}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Lịch học</div>
                <div class="fw-semibold">
                    ${getDayOfWeek(info.day_of_week)} (${formatTime(info.start_time)} - ${formatTime(info.end_time)})
                </div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Phòng học</div>
                <div class="fw-semibold">${info.room}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-secondary">Sĩ số</div>
                <div class="fw-semibold">${info.current_students} / ${info.max_students}</div>
            </div>
        `;
    }

    // Hàm điền danh sách sinh viên
    function populateStudentList(students) {
        studentListTitle.textContent = `Danh sách Sinh viên (${students.length})`;
        
        if (students.length === 0) {
            studentListTbody.innerHTML = '<tr><td colspan="4" class="text-center">Chưa có sinh viên nào trong lớp.</td></tr>';
            return;
        }

        let html = '';
        students.forEach(student => {
            html += `
                <tr data-enrollment-id="${student.enrollment_id}">
                    <td class="no-wrap">${student.student_code}</td>
                    <td class="fw-semibold">${student.name}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm grade-input" 
                               data-type="midterm" 
                               min="0" max="10" step="0.1" 
                               value="${student.midterm_score || ''}">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm grade-input" 
                               data-type="final" 
                               min="0" max="10" step="0.1" 
                               value="${student.final_score || ''}">
                    </td>
                </tr>
            `;
        });
        studentListTbody.innerHTML = html;
    }

    // 5. GẮN SỰ KIỆN CHO NÚT "LƯU TẤT CẢ ĐIỂM"
    saveAllGradesBtn.addEventListener('click', function() {
        const gradesData = [];
        const rows = studentListTbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const enrollmentId = row.dataset.enrollmentId;
            if (enrollmentId) {
                const midtermScore = row.querySelector('input[data-type="midterm"]').value;
                const finalScore = row.querySelector('input[data-type="final"]').value;
                
                gradesData.push({
                    enrollment_id: enrollmentId,
                    midterm_score: midtermScore === '' ? null : parseFloat(midtermScore),
                    final_score: finalScore === '' ? null : parseFloat(finalScore)
                });
            }
        });

        // Vô hiệu hóa nút
        saveAllGradesBtn.disabled = true;
        saveAllGradesBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i> Đang lưu...';

        // Gửi dữ liệu lên API
        fetch(API_UPDATE_GRADES_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ grades: gradesData }) // Gửi đi mảng dữ liệu
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Cập nhật điểm thành công!');
            } else {
                throw new Error(result.message || 'Lỗi khi cập nhật điểm');
            }
        })
        .catch(error => {
            console.error('Lỗi khi lưu điểm:', error);
            alert(`Lỗi: ${error.message}`);
        })
        .finally(() => {
            // Kích hoạt lại nút
            saveAllGradesBtn.disabled = false;
            saveAllGradesBtn.innerHTML = '<i class="bi bi-floppy-fill me-2"></i> Lưu tất cả điểm';
        });
    });

});