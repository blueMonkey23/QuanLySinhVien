document.addEventListener('DOMContentLoaded', function() {
    
    const API_URL_LIST = `${CONFIG.API_BASE_URL}/manager_classes.php`;
    const API_URL_DELETE = `${CONFIG.API_BASE_URL}/manager_class_delete.php`;
    const API_URL_LOCK = `${CONFIG.API_BASE_URL}/manager_class_lock.php`;
    const API_FETCH_DATA = `${CONFIG.API_BASE_URL}/class_data_fetch.php`; // API lấy danh sách môn học

    // Các phần tử DOM
    const tableBody = document.getElementById('class-list-tbody');
    const classCount = document.getElementById('class-count');
    
    // Các phần tử bộ lọc
    const searchInput = document.getElementById('searchClass');
    const filterCourse = document.getElementById('filterCourse');
    const filterSubject = document.getElementById('filterSubject');
    const btnFilter = document.getElementById('btnFilter');

    if (!tableBody || !classCount) return;

    // --- 1. TẢI DỮ LIỆU CHO DROPDOWN MÔN HỌC ---
    async function loadFilterOptions() {
        try {
            const res = await fetch(API_FETCH_DATA);
            const data = await res.json();
            if (data.success) {
                // Điền options vào select Môn học
                data.data.subjects.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id; // Value là ID
                    opt.textContent = sub.name;
                    filterSubject.appendChild(opt);
                });
            }
        } catch (e) { console.error("Lỗi tải bộ lọc:", e); }
    }

    // --- 2. HÀM TẢI DANH SÁCH LỚP (CÓ THAM SỐ) ---
    function loadClasses() {
        // Lấy giá trị từ bộ lọc
        const query = searchInput.value.trim();
        const subject = filterSubject.value;

        // Tạo URL chỉ còn tham số q và subject
        const url = `${API_URL_LIST}?q=${encodeURIComponent(query)}&subject=${subject}`;

        tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-5"><div class="spinner-border text-primary"></div><div>Đang tìm kiếm...</div></td></tr>`;

        fetch(url)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    renderTable(result.data);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${result.message}</td></tr>`;
                }
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Lỗi kết nối server.</td></tr>`;
            });
    }

    // --- 3. RENDER TABLE (Giữ nguyên logic hiển thị 4 nút) ---
    function getStatusBadge(endDate) { /* ... Giữ nguyên code cũ ... */
        if (!endDate) return '<span class="badge-status">Không xác định</span>';
        const classEndDate = new Date(endDate);
        const today = new Date();
        classEndDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        return (classEndDate < today) ? 
            '<span class="badge-status" style="background-color: #f0f2f6; color: #555;">Đã kết thúc</span>' : 
            '<span class="badge-status" style="background-color: #eaf3ff; color: #0d6efd;">Đang diễn ra</span>';
    }

    function renderTable(classes) {
        if (classes.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Không tìm thấy lớp học nào phù hợp.</td></tr>';
            classCount.textContent = '0 lớp';
            return;
        }

        let html = '';
        classes.forEach(cls => {
            const teacherName = cls.teacher_name || '<span class="text-muted fst-italic">Chưa gán</span>';
            const isLocked = cls.is_locked == 1;
            const rowClass = isLocked ? 'table-light text-muted' : ''; 
            const lockIcon = isLocked ? 'bi-lock-fill' : 'bi-unlock';
            const lockColor = isLocked ? 'btn-warning' : 'btn-outline-secondary';
            const editClass = isLocked ? 'btn-secondary disabled' : 'btn-outline-secondary';
            const editLink = isLocked ? '#' : `manager_class_edit.html?id=${cls.class_id}`;

            html += `
                <tr class="${rowClass}">
                    <td class="no-wrap">${cls.class_code}</td>
                    <td class="fw-semibold">${cls.subject_name}</td>
                    <td>${teacherName}</td>
                    <td>${cls.semester_name}</td>
                    <td>${cls.current_students} / ${cls.max_students}</td>
                    <td>${getStatusBadge(cls.end_date)}</td>
                    <td class="actions no-wrap">
                        <a href="manager_class_detail.html?id=${cls.class_id}" class="btn btn-sm btn-outline-primary me-1" title="Xem"><i class="bi bi-eye-fill"></i></a>
                        <button class="btn btn-sm ${lockColor} btn-lock me-1" data-id="${cls.class_id}"><i class="bi ${lockIcon}"></i></button>
                        <a href="${editLink}" class="btn btn-sm ${editClass} me-1" ${isLocked ? 'aria-disabled="true"' : ''}><i class="bi bi-pencil-fill"></i></a>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${cls.class_id}"><i class="bi bi-trash-fill"></i></button>
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
        classCount.textContent = `Hiển thị ${classes.length} lớp học`;
    }

    // --- 4. SỰ KIỆN (Event Listeners) ---
    
    // Nút Lọc
   btnFilter.addEventListener('click', loadClasses);
    
    // Thay đổi Dropdown Môn học tự động load lại (Đã bỏ filterCourse)
    filterSubject.addEventListener('change', loadClasses);

    // Tìm kiếm: Debounce
    let timeout = null;
    searchInput.addEventListener('keyup', function() {
        clearTimeout(timeout);
        timeout = setTimeout(loadClasses, 500);
    });

    // Sự kiện bảng (Xóa, Khóa - Giữ nguyên)
    tableBody.addEventListener('click', function(event) {
        const deleteBtn = event.target.closest('.btn-delete');
        const lockBtn = event.target.closest('.btn-lock');

        if (deleteBtn) {
            if (confirm('Bạn có chắc chắn muốn xóa lớp này?')) {
                callApi(API_URL_DELETE, {id: deleteBtn.dataset.id});
            }
        }
        if (lockBtn) {
            callApi(API_URL_LOCK, {id: lockBtn.dataset.id});
        }
    }); 

    // Hàm gọi API chung cho Xóa/Khóa
    function callApi(url, data) {
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) loadClasses(); 
            else alert(res.message);
        })
        .catch(e => alert('Lỗi kết nối'));
    }

    // --- KHỞI CHẠY ---
    loadFilterOptions(); // Tải môn học vào dropdown
    loadClasses();       // Tải danh sách lớp
});