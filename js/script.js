// js/script.js

// --- CẤU HÌNH ĐƯỜNG DẪN GỐC ---
// Nếu thư mục dự án trong C:/laragon/www là "quanlysinhvien" thì URL này ĐÚNG.
const API_BASE_URL = 'http://localhost:8080/backend';

document.addEventListener('DOMContentLoaded', function() {
    const authButtons = document.getElementById('authButtons');
    const API_STATUS_URL = `${API_BASE_URL}/status.php`; 
    const API_LOGOUT_URL = `${API_BASE_URL}/logout.php`; 

    // 1. Kiểm tra trạng thái đăng nhập
    fetch(API_STATUS_URL)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.logged_in) {
                updateAuthUI(data.data);
            } else {
                updateAuthUI(null);
                // Nếu không phải trang login/register thì đá về login
                const page = window.location.pathname.split('/').pop();
                if (page !== 'login.html' && page !== 'register.html') {
                    window.location.href = 'login.html';
                }
            }
        })
        .catch(error => console.error('Lỗi Auth:', error));

    // 2. Cập nhật giao diện User
    function updateAuthUI(user) {
        if (user) {
            authButtons.innerHTML = `
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> ${user.fullname}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted">${user.identifier || user.role}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="logoutBtn">Đăng xuất</a></li>
                    </ul>
                </div>
            `;
            
            // Gắn sự kiện Logout
            document.getElementById('logoutBtn').addEventListener('click', (e) => {
                e.preventDefault();
                fetch(API_LOGOUT_URL).then(() => window.location.href = 'login.html');
            });
        } else {
            authButtons.innerHTML = '';
        }
    }

    // 3. Active Sidebar tự động
    const path = window.location.pathname;
    const page = path.split("/").pop(); 
    const menuMap = {
        'manager_dashboard.html': 'link-dashboard',
        'manager_classes.html': 'link-classes',
        'manager_class_detail.html': 'link-classes',
        'add_Class.html': 'link-add-class',
        'manager_students.html': 'link-students'
    };
    const activeId = menuMap[page];
    if (activeId) {
        const link = document.getElementById(activeId);
        if (link) link.classList.add('active'); // Cần CSS .active
    }

    // 4. Toggle Sidebar Mobile
    const toggleBtn = document.getElementById("toggle-btn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            if(overlay) overlay.classList.toggle("active");
        });
        if(overlay) {
            overlay.addEventListener("click", () => {
                sidebar.classList.remove("active");
                overlay.classList.remove("active");
            });
        }
    }
});