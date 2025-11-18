// Nếu thư mục trong C:\laragon\www là "quanlysinhvien" thì URL dưới là đúng
const API_BASE = 'http://localhost:8080/backend'; 

document.addEventListener('DOMContentLoaded', () => {
    loadStudents();
});

async function loadStudents() {
    const keyword = document.getElementById('searchStudent').value;
    const tbody = document.getElementById('student-list-tbody');
    
    try {
        const res = await fetch(`${API_BASE}/students.php?action=list&keyword=${keyword}`);
        const json = await res.json();
        
        tbody.innerHTML = '';
        if (json.data && json.data.length > 0) {
            json.data.forEach(std => {
                const statusHtml = std.status == 1 
                    ? '<span class="badge bg-success">Hoạt động</span>' 
                    : '<span class="badge bg-danger">Đã khóa</span>';
                
                const row = `
                    <tr>
                        <td>${std.student_code}</td>
                        <td>${std.fullname}</td>
                        <td>${std.email || ''}</td>
                        <td>${std.dob || ''}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" onclick="viewSchedule(${std.id})"><i class="bi bi-calendar3"></i></button>
                            <button class="btn btn-sm btn-warning" onclick="editStudent(${std.id}, '${std.student_code}', '${std.fullname}', '${std.email}', '${std.dob}', '${std.gender}', '${std.address}')"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-secondary" onclick="toggleLock(${std.id})"><i class="bi bi-lock"></i></button>
                        </td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>';
        }
    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Lỗi kết nối API</td></tr>';
    }
}

async function viewSchedule(studentId) {
    const tbody = document.getElementById('schedule-tbody');
    document.getElementById('scheduleStudentName').innerText = '...';
    document.getElementById('no-class-msg').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();

    try {
        const res = await fetch(`${API_BASE}/get_student_classes.php?id=${studentId}`);
        const data = await res.json();

        if (data.success) {
            document.getElementById('scheduleStudentName').innerText = data.student.name;
            tbody.innerHTML = '';
            if (data.classes.length > 0) {
                data.classes.forEach(cls => {
                    const diem = (cls.midterm_score||'-') + ' / ' + (cls.final_score||'-');
                    tbody.innerHTML += `
                        <tr>
                            <td class="fw-bold">${cls.class_code}</td>
                            <td>${cls.subject_name}</td>
                            <td>${cls.teacher_name || ''}</td>
                            <td>${cls.day_text}<br>${cls.time_text}</td>
                            <td>${cls.room}</td>
                            <td class="text-center fw-bold">${diem}</td>
                        </tr>`;
                });
            } else {
                document.getElementById('no-class-msg').classList.remove('d-none');
            }
        }
    } catch (error) { console.error(error); }
}

function openModal() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentId').value = '';
    document.getElementById('studentCode').readOnly = false;
    document.getElementById('modalTitle').innerText = 'Thêm Sinh viên';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

function editStudent(id, code, name, email, dob, gender, address) {
    document.getElementById('studentId').value = id;
    document.getElementById('studentCode').value = code;
    document.getElementById('studentCode').readOnly = true;
    document.getElementById('fullname').value = name;
    document.getElementById('email').value = email;
    document.getElementById('dob').value = dob;
    document.getElementById('gender').value = gender;
    document.getElementById('address').value = address;
    document.getElementById('modalTitle').innerText = 'Cập nhật Sinh viên';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

async function saveStudent() {
    const id = document.getElementById('studentId').value;
    const data = {
        id: id,
        student_code: document.getElementById('studentCode').value,
        fullname: document.getElementById('fullname').value,
        email: document.getElementById('email').value,
        dob: document.getElementById('dob').value,
        gender: document.getElementById('gender').value,
        address: document.getElementById('address').value
    };
    await fetch(`${API_BASE}/students.php?action=${id?'update':'create'}`, {
        method: 'POST',
        body: JSON.stringify(data)
    });
    location.reload(); // Reload trang cho nhanh
}

async function toggleLock(id) {
    if(confirm('Đổi trạng thái?')) {
        await fetch(`${API_BASE}/students.php?action=toggle_lock`, {
            method: 'POST',
            body: JSON.stringify({id: id})
        });
        loadStudents();
    }
}