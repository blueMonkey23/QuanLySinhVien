const API_BASE = `${CONFIG.API_BASE_URL}`; 

document.addEventListener('DOMContentLoaded', () => {
    loadStudents();
});

async function loadStudents() {
    const keyword = document.getElementById('searchStudent').value;
    const tbody = document.getElementById('student-list-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Đang tải dữ liệu...</td></tr>';

    try {
        const res = await fetch(`${API_BASE}/students.php?action=list&keyword=${keyword}`);
        const json = await res.json();
        
        tbody.innerHTML = '';
        if (json.data && json.data.length > 0) {
            json.data.forEach(std => {
                const statusBadge = std.status == 1 
                    ? '<span class="badge bg-success">Hoạt động</span>' 
                    : '<span class="badge bg-danger">Đã khóa</span>';
                
                const btnLockIcon = std.status == 1 ? 'bi-unlock' : 'bi-lock-fill';
                const btnLockTitle = std.status == 1 ? 'Khóa hồ sơ' : 'Mở khóa hồ sơ';
                
                const row = `
                    <tr>
                        <td class="fw-bold text-primary">${std.student_code}</td>
                        <td>${std.fullname}</td>
                        <td>${std.email || '-'}</td>
                        <td>${std.dob || '-'}</td>
                        <td>${std.gender_text}</td>
                        <td>${statusBadge}</td>
                        <td class="text-center">
                            <div class="btn-group shadow-sm" role="group">
                                <button class="btn btn-sm btn-info text-white" onclick="viewSchedule(${std.id})" title="Xem Lớp & TKB">
                                    <i class="bi bi-calendar3"></i>
                                </button>
                                <button class="btn btn-sm btn-warning text-white" onclick="editStudent(${std.id}, '${std.student_code}', '${std.fullname}', '${std.email}', '${std.dob}', '${std.gender_text}', '${std.address}')" title="Sửa thông tin">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="toggleLock(${std.id})" title="${btnLockTitle}">
                                    <i class="bi ${btnLockIcon}"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Không tìm thấy sinh viên nào.</td></tr>';
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Lỗi kết nối đến máy chủ!</td></tr>';
    }
}

async function viewSchedule(studentId) {
    const tbody = document.getElementById('schedule-tbody');
    const nameSpan = document.getElementById('scheduleStudentName');
    const msg = document.getElementById('no-class-msg');
    
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3">Đang tải dữ liệu...</td></tr>';
    nameSpan.innerText = '...';
    msg.classList.add('d-none');
    
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();

    try {
        const res = await fetch(`${API_BASE}/get_student_classes.php?id=${studentId}`);
        const data = await res.json();

        if (data.success) {
            nameSpan.innerText = data.student.name + ' (' + data.student.student_code + ')';
            tbody.innerHTML = '';

            if (data.classes.length > 0) {
                data.classes.forEach(cls => {
                    const diem = (cls.midterm_score !== null ? cls.midterm_score : '-') + ' / ' + (cls.final_score !== null ? cls.final_score : '-');
                    const row = `
                        <tr>
                            <td class="fw-bold">${cls.class_code}</td>
                            <td>
                                ${cls.subject_name}<br>
                                <span class="badge bg-light text-dark border">${cls.format}</span>
                            </td>
                            <td>${cls.teacher_name || '<span class="text-muted">Chưa gán</span>'}</td>
                            <td>
                                <span class="badge bg-primary">${cls.day_text}</span><br>
                                <small>${cls.time_text}</small>
                            </td>
                            <td class="fw-bold text-secondary">${cls.room}</td>
                            <td class="text-center fw-bold">${diem}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } else {
                msg.classList.remove('d-none');
                tbody.innerHTML = '';
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error(error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu!</td></tr>';
    }
}

// 3. Mở Modal Thêm mới
function openModal() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('studentCode').readOnly = false;
    document.getElementById('modalTitle').innerText = 'Thêm Sinh viên Mới';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

// 4. Mở Modal Sửa
function editStudent(id, code, name, email, dob, genderText, address) {
    document.getElementById('studentId').value = id;
    document.getElementById('studentCode').value = code;
    document.getElementById('studentCode').readOnly = true;
    document.getElementById('fullname').value = name;
    document.getElementById('email').value = email;
    document.getElementById('dob').value = dob;
    
    // Map lại giới tính cho Select box
    let genderVal = 'Nam';
    if (genderText === 'Nữ') genderVal = 'Nữ';
    if (genderText === 'Khác') genderVal = 'Khác';
    document.getElementById('gender').value = genderVal;

    document.getElementById('address').value = address;
    
    document.getElementById('modalTitle').innerText = 'Cập nhật Thông tin';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

// 5. Lưu dữ liệu (Thêm hoặc Sửa)
async function saveStudent() {
    const id = document.getElementById('studentId').value;
    const action = id ? 'update' : 'create';
    
    const data = {
        id: id,
        student_code: document.getElementById('studentCode').value.trim(),
        fullname: document.getElementById('fullname').value.trim(),
        email: document.getElementById('email').value.trim(),
        dob: document.getElementById('dob').value,
        gender: document.getElementById('gender').value,
        address: document.getElementById('address').value.trim()
    };

    // Validate cơ bản
    if (!data.student_code || !data.fullname || !data.email) {
        alert('Vui lòng điền đầy đủ các trường bắt buộc!');
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/students.php?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if(result.success) {
            alert(result.message);
            // Ẩn modal
            const modalEl = document.getElementById('studentModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if(modal) modal.hide();
            
            loadStudents(); // Tải lại bảng
        } else {
            alert(result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Lỗi hệ thống khi lưu dữ liệu');
    }
}

// 6. Khóa / Mở khóa hồ sơ
async function toggleLock(id) {
    if(!confirm('Bạn có chắc chắn muốn thay đổi trạng thái (Khóa/Mở) sinh viên này?')) return;
    
    try {
        const res = await fetch(`${API_BASE}/students.php?action=toggle_lock`, {
            method: 'POST',
            body: JSON.stringify({id: id})
        });
        const result = await res.json();
        if (result.success) {
            loadStudents(); // Tải lại bảng để cập nhật icon
        } else {
            alert(result.message);
        }
    } catch (err) {
        console.error(err);
        alert('Lỗi khi thay đổi trạng thái');
    }
}