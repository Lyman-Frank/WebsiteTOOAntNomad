document.getElementById('registrationForm').addEventListener('submit', function (e) {
    e.preventDefault();
    let formData = new FormData(this);

    const dob = new Date(document.getElementById('dob').value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    const dayDiff = today.getDate() - dob.getDate();
    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
        age--;
    }

    if (age < 18 || age > 100) {
        showNotification('Возраст должен быть не менее 18 лет и не более 100 лет.', false);
        return;
    }

    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (password !== confirmPassword) {
        showNotification('Пароли не совпадают.', false);
        return;
    }

    if (password.length < 6) {
        showNotification('Пароль должен быть не менее 6 символов.', false);
        return;
    }

    if (!/[A-Z]/.test(password)) {
        showNotification('Пароль должен содержать хотя бы одну заглавную букву.', false);
        return;
    }

    if (!/[a-z]/.test(password)) {
        showNotification('Пароль должен содержать хотя бы одну строчную букву.', false);
        return;
    }

    if (!/[0-9]/.test(password)) {
        showNotification('Пароль должен содержать хотя бы одну цифру.', false);
        return;
    }

    if (!/[!@#$%^&*(),.?":{}|<>_-]/.test(password)) {
        showNotification('Пароль должен содержать хотя бы один специальный символ.', false);
        return;
    }

    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success);
        if (data.success) {
            setTimeout(() => {
                window.location.href = '../login.html';
            }, 2000);
        }
    })
    .catch(error => console.error('Error:', error));
});

function showNotification(message, success) {
    let notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.backgroundColor = success ? 'green' : 'red';

    document.body.appendChild(notification);

    setTimeout(() => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    }, 2000);

    notification.addEventListener('click', () => {
        if (document.body.contains(notification)) {
            document.body.removeChild(notification);
        }
    });
}
