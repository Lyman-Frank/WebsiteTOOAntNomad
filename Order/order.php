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
    <title>Заказ - ТOO "Ant Nomad"</title>
    <link rel="stylesheet" href="Order_styles.css">
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
                xhr.open("POST", "../update_theme.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("theme=" + isDark);
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
            <li><a href="../Profile/profile.php">Профиль</a></li>
            <li><a href="order.php" class="active">Заказать</a></li>
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
        <div class="order-container">
            <h2>Меню заказа</h2>
            <form action="submit_order.php" method="post">
                <div class="form-group">
                    <label for="projectName">Название Вашего проекта</label>
                    <input type="text" id="projectName" name="projectName" placeholder="Например: Разработка веб-приложения для TOO 'Ant Nomad'" required>
                </div>
                <div class="form-group">
                    <label for="projectDescription">Опишите полностью свой проект в деталях</label>
                    <textarea id="projectDescription" name="projectDescription" rows="10" required></textarea>
                </div>
                <div class="form-group">
                    <label for="deadline">Срок выполнения</label>
                    <select id="deadline" name="deadline" required>
                        <option value="Как можно раньше">Как можно раньше</option>
                        <option value="За неделю">За неделю</option>
                        <option value="За месяц">За месяц</option>
                        <option value="За 3 месяца">За 3 месяца</option>
                        <option value="За год">За год</option>
                        <option value="Не срочно">Не срочно</option>
                    </select>
                </div>
                <small class="note">*Проджект-менеджер может изменить время выполнение в зависимости от требований</small>
                <div class="form-group" id="budget-group">
                    <label for="budget">Примерный Ваш бюджет:</label>
                    <input type="number" id="budget" name="budget" required>
                </div>
                <p><small>После отправки Вы сможете увидеть статус заказа в <a href="../My_Orders/my_orders.php">Мои заказы</a></small></p>
                <button type="submit">Отправить</button>
            </form>
        </div>
    </main>
    <!-- Модальное окно для подтверждения выхода -->
    <div id="exit_modal" class="exit_modal">
        <div class="exit_modal-content">
            <span class="exit_modal-close">&times;</span>
            <p>Вы уверены, что хотите выйти с аккаунта?</p>
            <button id="exit_modal-confirm-logout" class="exit_modal-confirm-button">Да</button>
            <button id="exit_modal-cancel-logout" class="exit_modal-cancel-button">Нет</button>
        </div>
    </div>
    <footer>
        <div class="footer-content">
            <p>ТOO "Ant Nomad" © 2016-2024. Все права защищены.</p>
            <p>Работаем с 2016 года, предоставляем высококачественные услуги по разработке ПО и консультированию</p>
        </div>
    </footer>
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

        document.getElementById('theme-switch').addEventListener('change', function() {
            document.body.classList.toggle('dark-theme');
        });
    </script>
</body>
</html>
