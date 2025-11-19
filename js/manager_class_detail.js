document.addEventListener('DOMContentLoaded', function() {
    
    // Định nghĩa API
    const API_URL_DETAIL = `${CONFIG.API_BASE_URL}/manager_class_detail.php`;
    const API_URL_UPDATE_GRADES = `${CONFIG.API_BASE_URL}/manager_update_grades.php`;
    const API_URL_ENROLLMENT = `${CONFIG.API_BASE_URL}/manager_enroll_student.php`;

    // Lấy ID lớp từ URL
    const params = new URLSearchParams(window.location.search);
    const classId = params.get('id');

    if (!classId) {
        alert('Không tìm thấy ID lớp học.');
        window.location.href = 'manager_classes.html';
        return;
    }

    // Các phần tử DOM hiển thị thông tin
    const classCodeDisplay = document.getElementById('class_code_display');
    const subjectName = document.getElementById('subject_name');
    const teacherName = document.getElementById('teacher_name');
    const semesterName = document.getElementById('semester_name');
    const scheduleDisplay = document.getElementById('schedule_display');
    const roomDisplay = document.getElementById('room_display');
    const currentStudents = document.getElementById('current_students_display');
    const maxStudents = document.getElementById('max_students_display');
    const statusDisplay = document.getElementById('status_display');
    const formatDisplay = document.getElementById('format'); // Thêm hiển thị hình thức

    // Các phần tử bảng và form
    const studentGradesTbody = document.getElementById('student_grades_tbody');
    const saveGradesBtn = document.getElementById('save_grades_btn');
    const enrollmentForm = document.getElementById('enrollment_form');
    const studentCodeInput = document.getElementById('student_code_input');
    const addStudentBtn = document.getElementById('add_student_btn');
    const removeStudentBtn = document.getElementById('remove_student_btn'); // Nút xóa cạnh ô input
    const actionMessage = document.getElementById('action_message');
    const errorMessage = document.getElementById('grades_error_message');

    let classIsLocked = false;

    // --- 1. HÀM HIỂN THỊ THÔNG BÁO ---
    function showMessage(msg, type = 'success') {
        actionMessage.textContent = msg;
        actionMessage.className = type === 'success' ? 'small fw-bold text-success' : 'small fw-bold text-danger';
        // Tự động ẩn sau 3 giây
        setTimeout(() => { actionMessage.textContent = ''; }, 3000);
    }

    function getStatusBadge(endDate) {
        if (!endDate) return '';
        const end = new Date(endDate);
        const now = new Date();
        end.setHours(0,0,0,0); now.setHours(0,0,0,0);
        return end < now 
            ? '<span class="badge bg-secondary">Đã kết thúc</span>' 
            : '<span class="badge bg-primary">Đang diễn ra</span>';
    }

    // --- 2. TẢI DỮ LIỆU LỚP HỌC ---
    async function loadClassData() {
        try {
            const res = await fetch(`${API_URL_DETAIL}?id=${classId}`);
            const result = await res.json();

            if (result.success && result.data.length > 0) {
                const info = result.data[0]; // Lấy thông tin lớp từ dòng đầu tiên
                
                // Điền thông tin chung
                classCodeDisplay.textContent = info.class_code;
                subjectName.textContent = info.subject_name;
                teacherName.textContent = info.teacher_name || 'Chưa gán';
                semesterName.textContent = info.semester_name;
                roomDisplay.textContent = info.room;
                if (formatDisplay) formatDisplay.textContent = info.format;
                
                // Map ngày học (Số -> Chữ)
                const dayMap = {2:'Thứ Hai', 3:'Thứ Ba', 4:'Thứ Tư', 5:'Thứ Năm', 6:'Thứ Sáu', 7:'Thứ Bảy', 8:'Chủ Nhật'};
                const dayStr = dayMap[info.day_of_week] || info.day_of_week;
                const timeStr = `${info.start_time.substring(0,5)} - ${info.end_time.substring(0,5)}`;
                scheduleDisplay.textContent = `${dayStr} (${timeStr})`;

                currentStudents.textContent = info.current_students;
                maxStudents.textContent = info.max_students;
                statusDisplay.innerHTML = getStatusBadge(info.end_date);

                // Kiểm tra trạng thái khóa
                classIsLocked = info.is_locked == 1;
                updateLockState();

                // Render bảng sinh viên
                renderStudentTable(result.data);

            } else {
                alert('Không tìm thấy dữ liệu hoặc lớp học không tồn tại.');
                window.location.href = 'manager_classes.html';
            }
        } catch (e) {
            console.error(e);
            errorMessage.textContent = 'Lỗi kết nối server khi tải dữ liệu.';
        }
    }

    // --- 3. RENDER BẢNG SINH VIÊN ---
    function renderStudentTable(data) {
        // Kiểm tra nếu mảng data chỉ có thông tin lớp mà không có sinh viên (student_id là null)
        if (!data[0].student_id) {
            studentGradesTbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có sinh viên nào.</td></tr>';
            return;
        }

        let html = '';
        data.forEach((row, index) => {
            // Xử lý null -> chuỗi rỗng để hiển thị trong input
            const diligence = row.diligence_score !== null ? row.diligence_score : '';
            const mid = row.midterm_score !== null ? row.midterm_score : '';
            const final = row.final_score !== null ? row.final_score : '';

            html += `
                <tr data-enrollment-id="${row.enrollment_id}">
                    <td class="text-center">${index + 1}</td>
                    <td>${row.student_code}</td>
                    <td class="fw-semibold">${row.student_name}</td>
                    
                    <td>
                        <input type="number" step="0.1" min="0" max="10" class="form-control form-control-sm text-center grade-input" 
                               data-type="diligence_score" value="${diligence}" ${classIsLocked ? 'disabled' : ''}>
                    </td>

                    <td>
                        <input type="number" step="0.1" min="0" max="10" class="form-control form-control-sm text-center grade-input" 
                               data-type="midterm_score" value="${mid}" ${classIsLocked ? 'disabled' : ''}>
                    </td>

                    <td>
                        <input type="number" step="0.1" min="0" max="10" class="form-control form-control-sm text-center grade-input" 
                               data-type="final_score" value="${final}" ${classIsLocked ? 'disabled' : ''}>
                    </td>

                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-student" 
                                data-code="${row.student_code}" ${classIsLocked ? 'disabled' : ''} title="Xóa khỏi lớp">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        studentGradesTbody.innerHTML = html;
    }

    // --- 4. CẬP NHẬT TRẠNG THÁI KHÓA ---
    function updateLockState() {
        if (classIsLocked) {
            saveGradesBtn.disabled = true;
            addStudentBtn.disabled = true;
            if (removeStudentBtn) removeStudentBtn.disabled = true;
            studentCodeInput.disabled = true;
            errorMessage.textContent = 'Lớp học đang bị KHÓA. Không thể chỉnh sửa điểm số hoặc danh sách sinh viên.';
        } else {
            saveGradesBtn.disabled = false;
            addStudentBtn.disabled = false;
            if (removeStudentBtn) removeStudentBtn.disabled = false;
            studentCodeInput.disabled = false;
            errorMessage.textContent = '';
        }
    }

    // --- 5. XỬ LÝ LƯU ĐIỂM ---
    saveGradesBtn.addEventListener('click', async function() {
        if (classIsLocked) return;

        const rows = studentGradesTbody.querySelectorAll('tr');
        const gradesData = [];
        let hasError = false;

        rows.forEach(row => {
            const enrollId = row.dataset.enrollmentId;
            // Kiểm tra xem dòng này có phải là dòng dữ liệu thật không (có enrollmentId)
            if (enrollId) {
                const dilVal = row.querySelector('input[data-type="diligence_score"]').value;
                const midVal = row.querySelector('input[data-type="midterm_score"]').value;
                const finVal = row.querySelector('input[data-type="final_score"]').value;

                // Validate client-side đơn giản
                if ((dilVal && (dilVal < 0 || dilVal > 10)) || 
                    (midVal && (midVal < 0 || midVal > 10)) || 
                    (finVal && (finVal < 0 || finVal > 10))) {
                    hasError = true;
                    row.classList.add('table-danger'); // Highlight dòng lỗi
                } else {
                    row.classList.remove('table-danger');
                }

                gradesData.push({
                    enrollment_id: enrollId,
                    diligence_score: dilVal,
                    midterm_score: midVal,
                    final_score: finVal
                });
            }
        });

        if (hasError) {
            alert('Vui lòng kiểm tra lại. Điểm số phải từ 0 đến 10.');
            return;
        }
        
        if (gradesData.length === 0) {
            alert("Không có dữ liệu sinh viên để lưu.");
            return;
        }

        // Gửi API
        saveGradesBtn.disabled = true;
        saveGradesBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';

        try {
            const res = await fetch(API_URL_UPDATE_GRADES, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ grades: gradesData })
            });
            const result = await res.json();

            if (result.success) {
                showMessage(result.message);
            } else {
                showMessage(result.message, 'error');
            }
        } catch (e) {
            console.error(e);
            showMessage('Lỗi kết nối server.', 'error');
        } finally {
            saveGradesBtn.disabled = false;
            saveGradesBtn.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Lưu tất cả điểm';
        }
    });

    // --- 6. XỬ LÝ THÊM SINH VIÊN ---
    enrollmentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (classIsLocked) return;

        const code = studentCodeInput.value.trim();
        if (!code) return;

        addStudentBtn.disabled = true;
        try {
            const res = await fetch(API_URL_ENROLLMENT, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    class_id: classId,
                    student_code: code,
                    action: 'add'
                })
            });
            const result = await res.json();

            if (result.success) {
                showMessage(result.message);
                studentCodeInput.value = '';
                loadClassData(); // Tải lại bảng để hiện sinh viên mới
            } else {
                showMessage(result.message, 'error'); // VD: Lớp đầy, Mã sai...
            }
        } catch (e) {
            showMessage('Lỗi kết nối.', 'error');
        } finally {
            addStudentBtn.disabled = false;
        }
    });

    // --- 7. XỬ LÝ NÚT XÓA THỦ CÔNG (Cạnh ô input) ---
    if (removeStudentBtn) {
        removeStudentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (classIsLocked) return;

            const code = studentCodeInput.value.trim();
            if (!code) {
                alert("Vui lòng nhập Mã sinh viên để xóa.");
                return;
            }
            performDeleteStudent(code);
        });
    }

    // --- 8. XỬ LÝ NÚT XÓA TRONG BẢNG (Event Delegation) ---
    studentGradesTbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-student');
        if (!btn || classIsLocked) return;

        // SỬA LỖI: Ngăn chặn form submit mặc định
        e.preventDefault(); 

        const code = btn.dataset.code;
        performDeleteStudent(code);
    });

    // Hàm gọi API xóa chung
    async function performDeleteStudent(code) {
        if (confirm(`Bạn có chắc chắn muốn xóa sinh viên ${code} khỏi lớp?`)) {
            try {
                const res = await fetch(API_URL_ENROLLMENT, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        class_id: classId,
                        student_code: code,
                        action: 'remove'
                    })
                });
                const result = await res.json();
                if (result.success) {
                    showMessage(result.message);
                    loadClassData(); // Tải lại bảng
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Lỗi kết nối.');
            }
        }
    }

    // Khởi chạy
    loadClassData();
});
