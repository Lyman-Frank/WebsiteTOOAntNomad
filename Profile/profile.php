<?php
include('../auth_check.php');
include('../db_connect.php'); 

$userID = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT Login, Email, FullName, PhoneNumber, DateCreated, Address, Status, Theme FROM Users WHERE ID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($login, $email, $fullName, $phone, $dateCreated, $address, $status, $theme);
$stmt->fetch();
$stmt->close();

// Получение количества заказов пользователя
$orderCountQuery = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE UserID = ?");
$orderCountQuery->bind_param("i", $userID);
$orderCountQuery->execute();
$orderCountQuery->bind_result($orderCount);
$orderCountQuery->fetch();
$orderCountQuery->close();

$statusText = $status === 'active' ? 'активен' : 'неактивен';
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - ТOO "Ant Nomad"</title>
    <link rel="stylesheet" href="profile_styles.css">
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            // Устанавливаем тему при загрузке страницы
            var theme = <?php echo json_encode($theme); ?>;
            if (theme == 1) {
                document.body.classList.add('dark-theme');
                document.getElementById('theme-switch').checked = true;
            } else {
                document.body.classList.remove('dark-theme');
            }

            // Сохраняем тему при переключении
            document.getElementById('theme-switch').addEventListener('change', function() {
                var isDark = this.checked ? 1 : 0;
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "update_theme.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("theme=" + isDark);
            });

            // Модальное окно смены пароля
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
        });
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('change-password-form').addEventListener('submit', function (e) {
                e.preventDefault();
                var oldPassword = document.getElementById('old-password').value;
                var newPassword = document.getElementById('new-password').value;
                var confirmNewPassword = document.getElementById('confirm-new-password').value;

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "change_password.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    var response = JSON.parse(xhr.responseText);
                    var notification = document.createElement('div');
                    notification.classList.add('notification');

                    if (response.success) {
                        notification.classList.add('success');
                        notification.textContent = response.message;
                        document.getElementById('old-password').value = '';
                        document.getElementById('new-password').value = '';
                        document.getElementById('confirm-new-password').value = '';
                    } else {
                        notification.classList.add('error');
                        notification.textContent = response.message;
                    }

                    document.body.appendChild(notification);

                    setTimeout(function () {
                        document.body.removeChild(notification);
                    }, 3000);
                };

                var params = "old_password=" + encodeURIComponent(oldPassword) +
                            "&new_password=" + encodeURIComponent(newPassword) +
                            "&confirm_new_password=" + encodeURIComponent(confirmNewPassword);

                xhr.send(params);
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>ТOO "Ant Nomad"</h1>
        <div class="header-right">
            <div class="welcome-user">
                <small>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?></small>
            </div>
            <button type="button" class="logout-button">Выйти</button>
        </div>
    </header>
    <nav>
        <ul>
            <li><a href="../index.php">Главная</a></li>
            <li><a href="profile.php" class="active">Профиль</a></li>
            <li><a href="../Order/order.php">Заказать</a></li>
            <li class="my-orders">
                <a href="../My_Orders/my_orders.php">Мои заказы
                    <?php if ($orderCount > 0): ?>
                        <span class="order-count"><?php echo $orderCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="theme-toggle">
                <input type="checkbox" id="theme-switch" class="theme-switch">
                <label for="theme-switch" class="theme-label"></label>
            </li>
        </ul>
    </nav>
    <main>
        <div class="profile-container">
            <h2>Профиль</h2>
            <p>Email: <?php echo htmlspecialchars($email); ?></p>
            <p>ФИО: <?php echo htmlspecialchars($fullName); ?></p>
            <p>Телефон: <?php echo htmlspecialchars($phone); ?></p>
            <p>Дата регистрации: <?php echo htmlspecialchars($dateCreated); ?></p>
            <p>Адрес: <?php echo htmlspecialchars($address); ?></p>
            <p>Статус: <?php echo htmlspecialchars($statusText); ?></p>
            <button id="change-password-btn">Изменить пароль</button>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <p>ТOO "Ant Nomad" © 2016-2024. Все права защищены.</p>
            <p>Работаем с 2016 года, предоставляем высококачественные услуги по разработке ПО и консультированию</p>
        </div>
    </footer>

    <!-- Модальное окно для подтверждения выхода -->
    <div id="exit_modal" class="exit_modal">
        <div class="exit_modal-content">
            <span class="exit_modal-close">&times;</span>
            <p>Вы уверены, что хотите выйти с аккаунта?</p>
            <button id="exit_modal-confirm-logout" class="exit_modal-confirm-button">Да</button>
            <button id="exit_modal-cancel-logout" class="exit_modal-cancel-button">Нет</button>
        </div>
    </div>
    <div id="change-password-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Изменение пароля</h2>
            <form id="change-password-form" action="change_password.php" method="post">
                <label for="old-password">Старый пароль:</label>
                <input type="password" id="old-password" name="old_password" required>
                
                <label for="new-password">Новый пароль:</label>
                <input type="password" id="new-password" name="new_password" required>
                
                <label for="confirm-new-password">Подтверждение нового пароля:</label>
                <input type="password" id="confirm-new-password" name="confirm_new_password" required>
                
                <button type="submit">Изменить</button>
            </form>
        </div>
    </div>
    <script>
        document.querySelector('.logout-button').addEventListener('click', function() {
            document.getElementById('exit_modal').style.display = 'block';
        });

        document.querySelector('.exit_modal-close').addEventListener('click', function() {
            document.getElementById('exit_modal').style.display = 'none';
        });

        document.getElementById('exit_modal-cancel-logout').addEventListener('click', function() {
            document.getElementById('exit_modal').style.display = 'none';
        });

        document.getElementById('exit_modal-confirm-logout').addEventListener('click', function() {
            window.location.href = '../logout.php';
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('exit_modal')) {
                document.getElementById('exit_modal').style.display = 'none';
            }
        };
        document.getElementById('theme-switch').addEventListener('change', function() {
            document.body.classList.toggle('dark-theme');
        });
    </script>
</body>
</html>
