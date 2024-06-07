document.addEventListener('DOMContentLoaded', () => {
    const useMyOwnUserSystem = document.getElementById('useMyOwnUserSystem');
    const createMyOwnUserManagementControl = Array.from(document.getElementsByClassName('create-my-own-user-control'));
    const useManagementSelect = document.getElementById('user_management');
    const redisControl = Array.from(document.getElementsByClassName('redis-control'));
    const defaultControl = Array.from(document.getElementsByClassName('default-control'));
    const memcachedControl = Array.from(document.getElementsByClassName('memcache-control'));
    const toggleRedisPassword = document.getElementById('toggleRedisPassword');
    const redisPassword = document.getElementById('redis_password');
    const showRedisPass = document.getElementById('showRedisPass');

    useMyOwnUserSystem?.addEventListener('change', function () {
        const isChecked = this.checked;
        if (isChecked) {
            createMyOwnUserManagementControl.map(el => el.classList.add('d-none'));
        } else {
            createMyOwnUserManagementControl.map(el => el.classList.remove('d-none'));
        }
    });

    useManagementSelect?.addEventListener('change', (event) => {
        const cacheType = event.target.value;
        redisControl.map(el => el.classList.add('d-none'));
        defaultControl.map(el => el.classList.add('d-none'));
        memcachedControl.map(el => el.classList.add('d-none'));

        switch (cacheType) {
            case 'redis':
                redisControl.map(el => el.classList.remove('d-none'));
                break;

            case 'memcached':
                memcachedControl.map(el => el.classList.remove('d-none'));
                break;
            case 'default':
                defaultControl.map(el => el.classList.remove('d-none'));
                break;
            default:
                break;

        }
    });

    toggleRedisPassword?.addEventListener('click', () => {
        if (redisPassword.type === 'password') {
            redisPassword.type = 'text';
            showRedisPass.className = 'fa fa-eye-slash';
        } else {
            redisPassword.type = 'password';
            showRedisPass.className = 'fa fa-eye';
        }
    });
});
