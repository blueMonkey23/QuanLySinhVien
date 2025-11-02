document.addEventListener('DOMContentLoaded', function() {
    const authButtons = document.getElementById('authButtons');
    
    function updateAuthUI() {
        const currentUser = JSON.parse(localStorage.getItem('currentUser'));
        
        if (currentUser) {
            authButtons.innerHTML = `
              <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                <li class="nav-item dropdown ">
                      <a class="nav-link dropdown-toggle text-dark d-flex text-white align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-person fs-4"></i>
                          
                          <div class="ms-2 d-none d-sm-block">
                              <span class="d-block text-white" style="font-size: 0.9em; line-height: 1.2;">${currentUser.fullname}</span>
                              <span class="d-block small text-white" style="line-height: 1.2;">${currentUser.studentId}</span>
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
                <button class="btn me-2" id="btnLogin">Đăng nhập</button>
                <button class="btn btn-secondary fw-bold" id="btnRegister">Đăng ký</button>
            `;
        }
    }


    if (authButtons) {
        authButtons.addEventListener('click', function(event) {
            const targetId = event.target.id;

            if (targetId === 'logoutLink') {
                event.preventDefault(); 
                localStorage.removeItem('currentUser');
                alert('Đăng xuất thành công!');
                updateAuthUI(); // Cập nhật lại UI sau khi đăng xuất
            }

            if (targetId === 'btnLogin') {
                window.location.href = 'login.html';
            }

            if (targetId === 'btnRegister') {
                window.location.href = 'register.html';
            }
        });
    }
    updateAuthUI();
});

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