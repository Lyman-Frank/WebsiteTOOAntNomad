<?php
include('auth_check.php');
include('db_connect.php'); 

$userID = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT FullName, Theme FROM Users WHERE ID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($fullName, $theme);
$stmt->fetch();
$stmt->close();

// Получение количества заказов пользователя
$orderCountQuery = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE UserID = ?");
$orderCountQuery->bind_param("i", $userID);
$orderCountQuery->execute();
$orderCountQuery->bind_result($orderCount);
$orderCountQuery->fetch();
$orderCountQuery->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница - ТOO "Ant Nomad"</title>
    <link rel="stylesheet" href="styles.css">
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
            <li><a href="index.php" class="active">Главная</a></li>
            <li><a href="Profile/profile.php">Профиль</a></li>
            <li><a href="Order/order.php">Заказать</a></li>
            <li class="my-orders">
                <a href="My_Orders/my_orders.php">Мои заказы
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
        <div class="content">
            <div class="text">
                <h2>Добро пожаловать!</h2>
                <p> Компания<span class="company-name"> Тоo "Ant Nomad"</span> 
                    занимается разработкой программного обеспечения под индивидуальные требования клиентов. 
                    Мы предлагаем полный спектр услуг по созданию и поддержке программных решений, включая анализ требований, 
                    разработку, тестирование и внедрение. 
                    Наши специалисты имеют богатый опыт в различных областях и готовы реализовать проекты любой сложности. 
                    Свяжитесь с нами для получения консультации и начала сотрудничества.
                </p>
                <p>Мы ценим каждого клиента и стремимся предложить наилучшие решения, 
                    соответствующие вашим потребностям. Ваш успех - наша цель. 
                    Доверьтесь профессионалам и получите качественный продукт, 
                    который будет эффективно поддерживать ваш бизнес.
                    Наш адрес: г. Алматы, Бостандыкский район, проспект Гагарина, дом 124, Корпус 1.
                    На рынке больше 8 лет
                </p>
                <p>Для заказа перейдите в пункт <a href="Order/order.php" id="order-link">Заказать</a></p>
            </div>
            <div class="image">
                <img src="img/Users_start.jpg" alt="Компания ТOO Ant Nomad">
            </div>
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
