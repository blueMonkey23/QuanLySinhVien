// Thay thế toàn bộ nội dung tệp script.js của bạn
document.addEventListener('DOMContentLoaded', function() {
    const authButtons = document.getElementById('authButtons');
    const API_STATUS_URL = `${CONFIG.API_BASE_URL}/status.php`; 
    const API_LOGOUT_URL = `${CONFIG.API_BASE_URL}/logout.php`; 

    function updateAuthUI(currentUser) {
        if (currentUser) {
            const userIdentifier = currentUser.identifier || currentUser.role; 

            authButtons.innerHTML = `
                <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                  <li class="nav-item dropdown ">
                        <a class="nav-link dropdown-toggle text-dark d-flex text-white align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person fs-4"></i>
                            
                            <div class="ms-2 d-none d-sm-block">
                                <span class="d-block text-white" style="font-size: 0.9em; line-height: 1.2;">${currentUser.fullname}</span>
                                <span class="d-block small text-white" style="line-height: 1.2;">${userIdentifier}</span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">Xin chào, ${currentUser.fullname}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-header-hover" href="#" id="logoutLink">Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
                `;
            const newDropdownToggle = authButtons.querySelector('[data-bs-toggle="dropdown"]');
            if (newDropdownToggle) {
                new bootstrap.Dropdown(newDropdownToggle);
            }
        } else {
            authButtons.innerHTML = `
                <button class="btn me-2 text-white" id="btnLogin">Đăng nhập</button>
                <button class="btn btn-secondary fw-bold" id="btnRegister">Đăng ký</button>
            `;
        }
    }
    
    if (authButtons) {
        authButtons.addEventListener('click', function(event) {
            const targetId = event.target.id;
            if (targetId === 'logoutLink') {
                event.preventDefault(); 
                fetch(API_LOGOUT_URL, { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Đăng xuất thành công!');
                            updateAuthUI(null);
                            window.location.href = 'login.html'; // Chuyển về trang đăng nhập
                        } else {
                            alert('Lỗi đăng xuất: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi fetch đăng xuất:', error);
                        alert('Lỗi kết nối khi đăng xuất.');
                    });
            }
            if (targetId === 'btnLogin') {
                window.location.href = 'login.html';
            }
            if (targetId === 'btnRegister') {
                window.location.href = 'register.html';
            }
        });
    }

    // Kiểm tra trạng thái đăng nhập
    fetch(API_STATUS_URL)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.logged_in) {
                updateAuthUI(data.data);
            } else {
                updateAuthUI(null);
                // Nếu trang hiện tại không phải là login/register, chuyển hướng
                const currentPage = window.location.pathname.split('/').pop();
                if (currentPage !== 'login.html' && currentPage !== 'register.html') {
                    // Tự động chuyển về trang đăng nhập nếu chưa đăng nhập
                    window.location.href = 'login.html';
                }
            }
        })
        .catch(error => {
            console.error('Lỗi khi kiểm tra trạng thái đăng nhập:', error);
            updateAuthUI(null);
        });

    // Xử lý Sidebar
    const toggleBtn = document.getElementById("toggle-btn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");

    if (toggleBtn && sidebar && overlay) {
        toggleBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");
        });

        overlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });
    }
});