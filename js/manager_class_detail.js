// js/manager_class_detail.js
document.addEventListener('DOMContentLoaded', function() {

    const API_BASE = 'http://localhost:8080/backend';
    const params = new URLSearchParams(window.location.search);
    const classId = params.get('id');

    if (!classId) {
        alert('Thiếu ID lớp');
        window.location.href = 'manager_classes.html';
        return;
    }

    // 1. Load chi tiết
    function loadDetail() {
        fetch(`${API_BASE}/manager_class_detail.php?id=${classId}`)
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                renderInfo(res.data.class_info);
                renderStudents(res.data.students);
            } else {
                alert(res.message);
            }
        });
    }

    function renderInfo(info) {
        document.getElementById('page-title').textContent = `Lớp: ${info.subject_name}`;
        document.getElementById('class-code-subtitle').textContent = info.class_code;
        
        const days = {2:'Thứ 2',3:'Thứ 3',4:'Thứ 4',5:'Thứ 5',6:'Thứ 6',7:'Thứ 7',8:'CN'};
        const time = info.start_time ? `${info.start_time.slice(0,5)}-${info.end_time.slice(0,5)}` : '';

        document.getElementById('class-info-container').innerHTML = `
            <div class="col-md-4"><strong>GV:</strong> ${info.teacher_name}</div>
            <div class="col-md-4"><strong>Phòng:</strong> ${info.room}</div>
            <div class="col-md-4"><strong>Lịch:</strong> ${days[info.day_of_week]} (${time})</div>
            <div class="col-md-4"><strong>Sĩ số:</strong> ${info.current_students}/${info.max_students}</div>
        `;
    }

    function renderStudents(list) {
        const tbody = document.getElementById('student-list-tbody');
        document.getElementById('student-list-title').textContent = `Danh sách Sinh viên (${list.length})`;
        
        if(list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Chưa có sinh viên</td></tr>';
            return;
        }

        let html = '';
        list.forEach(st => {
            html += `
                <tr data-eid="${st.enrollment_id}">
                    <td>${st.student_code}</td>
                    <td>${st.name}</td>
                    <td><input type="number" class="form-control text-center" data-type="midterm" value="${st.midterm_score||''}"></td>
                    <td><input type="number" class="form-control text-center" data-type="final" value="${st.final_score||''}"></td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    // 2. Lưu điểm
    document.getElementById('save-all-grades-btn')?.addEventListener('click', () => {
        const grades = [];
        document.querySelectorAll('tr[data-eid]').forEach(row => {
            grades.push({
                enrollment_id: row.dataset.eid,
                midterm_score: row.querySelector('[data-type="midterm"]').value || null,
                final_score: row.querySelector('[data-type="final"]').value || null
            });
        });

        fetch(`${API_BASE}/manager_update_grades.php`, {
            method: 'POST',
            body: JSON.stringify({grades: grades})
        })
        .then(res => res.json())
        .then(res => alert(res.message));
    });

    // 3. Thêm SV (Global function)
    window.openAddStudentModal = function() {
        document.getElementById('inputStudentCode').value = '';
        new bootstrap.Modal(document.getElementById('addStudentToClassModal')).show();
    }

    window.submitAddStudent = function() {
        const code = document.getElementById('inputStudentCode').value.trim();
        if(!code) return alert('Nhập mã SV');

        fetch(`${API_BASE}/add_student_to_class.php`, {
            method: 'POST',
            body: JSON.stringify({class_id: classId, student_code: code})
        })
        .then(res => res.json())
        .then(res => {
            alert(res.message);
            if(res.success) location.reload();
        });
    }

    loadDetail();
});