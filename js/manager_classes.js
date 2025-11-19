// js/manager_classes.js
const API_URL = 'http://localhost:8080/backend/manager_classes.php';

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('class-list-tbody');
    const count = document.getElementById('class-count');

    fetch(API_URL)
        .then(res => res.json())
        .then(result => {
            if (result.success && result.data) {
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có lớp học nào</td></tr>';
                    count.textContent = '0 lớp';
                    return;
                }

                let html = '';
                result.data.forEach(cls => {
                    html += `
                        <tr>
                            <td>${cls.class_code}</td>
                            <td class="fw-semibold">${cls.subject_name}</td>
                            <td>${cls.teacher_name || 'Chưa gán'}</td>
                            <td>${cls.semester_name || '-'}</td>
                            <td>${cls.current_students} / ${cls.max_students}</td>
                            <td><span class="badge bg-success">Đang mở</span></td>
                            <td>
                                <a href="manager_class_detail.html?id=${cls.class_id}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Chi tiết</a>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
                count.textContent = `Hiển thị ${result.data.length} lớp`;
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>';
        });
});