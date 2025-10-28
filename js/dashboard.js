document.addEventListener('DOMContentLoaded', function() {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    const userDropdown = document.getElementById('userDropdown');
    if (currentUser) 
    {
        document.getElementById('usernameDisplay').textContent = currentUser.fullname;
        document.getElementById('studentIdDisplay').textContent = currentUser.studentId;
        userDropdown.innerHTML = `
            <li><span class="dropdown-item-text">Xin chào, ${currentUser.fullname}</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-header-hover" href="login.html" id="logoutLink">Đăng xuất</a></li>
        `
    };
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(){
            event.preventDefault();
            localStorage.removeItem('currentUser');
            userDropdown.innerHTML = `
                <li><a class="dropdown-item text-header-hover" href="login.html">Đăng nhập</a></li>
                <li><a class="dropdown-item text-header-hover" href="register.html">Đăng ký</a></li>
            `
            alert('Đăng xuất thành công!');
        })
    };
    
    
});