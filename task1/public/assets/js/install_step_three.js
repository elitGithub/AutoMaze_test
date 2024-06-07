const toggleAdminPassword = document.getElementById('toggleAdminPassword');
const toggleRetypePassword = document.getElementById('toggleRetypePassword');
const showRetypePassword = document.getElementById('showRetypePassword');
const showAdminPassword = document.getElementById('showAdminPassword');
const adminPassword = document.getElementById('admin_password');
const passwordRetype = document.getElementById('password_retype');
toggleAdminPassword?.addEventListener('click', () => {
    if (adminPassword.type === 'password') {
        adminPassword.type = 'text';
        showAdminPassword.className = 'fa fa-eye-slash';
    } else {
        adminPassword.type = 'password';
        showAdminPassword.className = 'fa fa-eye';
    }
});
toggleRetypePassword?.addEventListener('click', () => {
    if (passwordRetype.type === 'password') {
        passwordRetype.type = 'text';
        showRetypePassword.className = 'fa fa-eye-slash';
    } else {
        passwordRetype.type = 'password';
        showRetypePassword.className = 'fa fa-eye';
    }
});

