//js/manager_classes.js
document.addEventListener('DOMContentLoaded', function() {
    
    const API_URL = 'http://localhost:8080/backend/manager_classes.php';
    const tableBody = document.getElementById('class-list-tbody');
    const classCount = document.getElementById('class-count');

    // Hàm hiển thị trạng thái 
    function getStatusBadge(endDate) {
        const classEndDate = new Date(endDate);
        const today = new Date();
        
        classEndDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        if (classEndDate < today) {
            return '<span class="badge-status" style="background-color: #f0f2f6; color: #555;">Đã kết thúc</span>';
        } else {
            return '<span class="badge-status" style="background-color: #eaf3ff; color: #0d6efd;">Đang diễn ra</span>';
        }
    }

    // Hàm tải danh sách lớp học
    function loadClasses() {
        fetch(API_URL)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    renderTable(result.data);
                } else {
                    // Xử lý lỗi từ API
                    throw new Error(result.message || 'Không thể tải dữ liệu từ API');
                }
            })
            .catch(error => {
                // KHẮC PHỤC LỖI: Lỗi này không còn xảy ra nếu đã có kiểm tra ở đầu hàm
                // Nhưng chúng ta vẫn kiểm tra lần cuối nếu có trường hợp ngoại lệ
                console.error('Lỗi khi tải danh sách lớp:', error);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Lỗi: ${error.message}</td></tr>`;
                classCount.textContent = '0 lớp';
            });
    }

    // Hàm điền dữ liệu vào bảng (giữ nguyên)
    function renderTable(classes) {
        if (classes.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Không tìm thấy lớp học nào.</td></tr>';
            classCount.textContent = '0 lớp';
            return;
        }

        let html = '';
        classes.forEach(cls => {
            html += `
                <tr>
                    <td class="no-wrap">${cls.class_code}</td>
                    <td class="fw-semibold">${cls.subject_name}</td>
                    <td>${cls.teacher_name || 'Chưa gán'}</td>
                    <td>${cls.semester_name}</td>
                    <td>${cls.current_students} / ${cls.max_students}</td>
                    <td>${getStatusBadge(cls.end_date)}</td>
                    <td class="actions no-wrap">
                        <a href="manager_class_detail.html?id=${cls.class_id}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                            <i class="bi bi-eye-fill"></i>
                        </a>
                        <a href="manager_class_edit.html?id=${cls.class_id}" class="btn btn-sm btn-outline-secondary" title="Sửa">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-danger btn-delete" data-id="${cls.class_id}" title="Xóa">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;
        classCount.textContent = `Hiển thị ${classes.length} lớp học`;
    }

    // Chạy hàm tải lớp học khi trang được mở
    loadClasses();

});