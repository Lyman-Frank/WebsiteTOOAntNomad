document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    let formData = new FormData(this);

    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const role = data.role;
            if (role === 'admin') {
                window.location.href = '../managers_page.php';
            } else {
                window.location.href = '../index.php';
            }
        } else {
            showNotification(data.message, false);
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
