document.addEventListener('DOMContentLoaded', function() {
    
    const API_SCHEDULE = `${CONFIG.API_BASE_URL}/manager_schedule.php`;
    const API_FETCH_DATA = `${CONFIG.API_BASE_URL}/class_data_fetch.php`; // Tái sử dụng để lấy list giáo viên

    const filterTeacher = document.getElementById('filter_teacher');
    const filterRoom = document.getElementById('filter_room');
    const btnApply = document.getElementById('btn_apply_filter');
    const btnReset = document.getElementById('btn_reset_filter');

    // --- 1. TẢI DANH SÁCH GIÁO VIÊN CHO BỘ LỌC ---
    async function loadTeachers() {
        try {
            const res = await fetch(API_FETCH_DATA);
            const data = await res.json();
            if (data.success) {
                data.data.teachers.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.name; // Đã sửa ở bài trước thành Name đầy đủ
                    filterTeacher.appendChild(opt);
                });
            }
        } catch (e) { console.error(e); }
    }

    // --- 2. TẢI VÀ HIỂN THỊ LỊCH ---
    async function loadSchedule() {
        // Xóa dữ liệu cũ trên lưới
        document.querySelectorAll('.shift-cell').forEach(cell => cell.innerHTML = '');

        // Lấy tham số lọc
        const teacherId = filterTeacher.value;
        const room = filterRoom.value.trim();
        
        const url = `${API_SCHEDULE}?teacher_id=${teacherId}&room=${room}`;

        try {
            const res = await fetch(url);
            const result = await res.json();

            if (result.success && result.data) {
                renderSchedule(result.data);
            } else {
                console.error('Lỗi tải lịch:', result.message);
            }
        } catch (e) {
            console.error('Lỗi kết nối:', e);
        }
    }

    // --- 3. RENDER LÊN LƯỚI ---
    function renderSchedule(items) {
        items.forEach(item => {
            // Xác định Ca học dựa trên giờ bắt đầu
            // Giả định: < 12:00 là Sáng, < 18:00 là Chiều, còn lại là Tối
            let shift = '';
            const startHour = parseInt(item.start_time.split(':')[0]);
            
            if (startHour < 12) shift = 'morning';
            else if (startHour < 18) shift = 'afternoon';
            else shift = 'evening';

            // Tìm ô tương ứng trong lưới HTML
            // item.day_of_week: 2->8
            const cell = document.querySelector(`.shift-cell[data-day="${item.day_of_week}"][data-shift="${shift}"]`);

            if (cell) {
                // Tạo thẻ hiển thị lớp học
                const div = document.createElement('div');
                div.className = 'schedule-item';
                div.innerHTML = `
                    <div class="fw-bold text-primary">${item.subject_name}</div>
                    <div class="tiny text-muted">${item.class_code}</div>
                    <div class="tiny"><i class="bi bi-person"></i> ${item.teacher_name || 'Chưa gán'}</div>
                    <div class="tiny"><i class="bi bi-geo-alt"></i> ${item.room}</div>
                    <div class="tiny"><i class="bi bi-clock"></i> ${item.start_time.substring(0,5)} - ${item.end_time.substring(0,5)}</div>
                `;
                
                // Thêm sự kiện click để xem chi tiết
                div.addEventListener('click', () => {
                    window.location.href = `manager_class_detail.html?id=${item.class_id}`;
                });

                cell.appendChild(div);
            }
        });
    }

    // --- SỰ KIỆN ---
    btnApply.addEventListener('click', loadSchedule);
    
    btnReset.addEventListener('click', () => {
        filterTeacher.value = '';
        filterRoom.value = '';
        loadSchedule();
    });

    // Khởi chạy
    loadTeachers();
    loadSchedule();
});
