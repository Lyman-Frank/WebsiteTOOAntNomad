document.getElementById('change-password-btn').addEventListener('click', function() {
    document.getElementById('change-password-modal').style.display = 'block';
});

document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('change-password-modal').style.display = 'none';
});

window.onclick = function(event) {
    if (event.target == document.getElementById('change-password-modal')) {
        document.getElementById('change-password-modal').style.display = 'none';
    }
};

document.getElementById('change-password-form').addEventListener('submit', function(event) {
    const newPassword = document.getElementById('new-password').value;
    const confirmNewPassword = document.getElementById('confirm-new-password').value;

    if (newPassword !== confirmNewPassword) {
        alert('Новый пароль и подтверждение пароля не совпадают.');
        event.preventDefault();
        return;
    }

    if (newPassword.length < 6) {
        alert('Пароль должен быть не менее 6 символов.');
        event.preventDefault();
        return;
    }

    if (!/[A-Z]/.test(newPassword)) {
        alert('Пароль должен содержать хотя бы одну заглавную букву.');
        event.preventDefault();
        return;
    }

    if (!/[a-z]/.test(newPassword)) {
        alert('Пароль должен содержать хотя бы одну строчную букву.');
        event.preventDefault();
        return;
    }

    if (!/[0-9]/.test(newPassword)) {
        alert('Пароль должен содержать хотя бы одну цифру.');
        event.preventDefault();
        return;
    }

    if (!/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
        alert('Пароль должен содержать хотя бы один специальный символ.');
        event.preventDefault();
    }
});
