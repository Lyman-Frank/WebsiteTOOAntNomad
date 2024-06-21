<?php
include('../auth_check.php');
include('../db_connect.php'); 

$userID = $_SESSION['user_id'];

// Получение значения темы пользователя
$themeQuery = $conn->prepare("SELECT theme FROM Users WHERE id = ?");
$themeQuery->bind_param("i", $userID);
$themeQuery->execute();
$themeQuery->bind_result($theme);
$themeQuery->fetch();
$themeQuery->close();

// Получение количества заказов пользователя
$orderCountQuery = $conn->prepare("SELECT COUNT(*) FROM Orders WHERE UserID = ?");
$orderCountQuery->bind_param("i", $userID);
$orderCountQuery->execute();
$orderCountQuery->bind_result($orderCount);
$orderCountQuery->fetch();
$orderCountQuery->close();

// Получение заказов пользователя
$stmt = $conn->prepare("SELECT OrderNumber, Description, Status, CreatedAt, UpdatedAt, DueDate, Price FROM Orders WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($orderNumber, $description, $status, $createdAt, $updatedAt, $dueDate, $price);
$orders = [];
while ($stmt->fetch()) {
    $orders[] = [
        'orderNumber' => $orderNumber,
        'description' => $description,
        'status' => $status,
        'createdAt' => $createdAt,
        'updatedAt' => $updatedAt,
        'dueDate' => $dueDate,
        'price' => $price
    ];
}
$stmt->close();

// Получение заказов с статусом в обработке
$validOrdersQuery = $conn->prepare("SELECT OrderNumber FROM Orders WHERE UserID = ? AND Status = 'in processing'");
$validOrdersQuery->bind_param("i", $userID);
$validOrdersQuery->execute();
$validOrdersQuery->bind_result($validOrderNumber);
$validOrders = [];
while ($validOrdersQuery->fetch()) {
    $validOrders[] = $validOrderNumber;
}
$validOrdersQuery->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы - ТOO "Ant Nomad"</title>
    <link rel="stylesheet" href="my_orders_styles.css">
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            // Устанавливаем тему при загрузке страницы
            var theme = <?php echo json_encode($theme); ?>;
            var themeSwitch = document.getElementById('theme-switch');
            
            if (themeSwitch) {
                if (theme == 1) {
                    document.body.classList.add('dark-theme');
                    themeSwitch.checked = true;
                } else {
                    document.body.classList.remove('dark-theme');
                }

                // Сохраняем тему при переключении
                themeSwitch.addEventListener('change', function() {
                    var isDark = this.checked ? 1 : 0;
                    document.body.classList.toggle('dark-theme');
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "../update_theme.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send("theme=" + isDark);
                });
            }

            // Обработчик для кнопки отмены заказа
            document.getElementById('cancel-order-button').addEventListener('click', function() {
                var orderId = document.getElementById('order-select').value;
                document.getElementById('confirm-order-id').textContent = orderId;
                document.getElementById('confirm-cancel-modal').style.display = 'block';
            });

            // Обработчик для подтверждения отмены заказа
            document.getElementById('confirm-cancel-button').addEventListener('click', function() {
                var orderId = document.getElementById('order-select').value;
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "cancel_order.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("order_id=" + orderId);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert("Заказ отменен.");
                        location.reload();
                    } else {
                        alert("Ошибка при отмене заказа.");
                    }
                };
            });

            // Закрытие модального окна
            document.getElementById('cancel-modal-close').addEventListener('click', function() {
                document.getElementById('confirm-cancel-modal').style.display = 'none';
            });

            document.getElementById('cancel-modal-cancel').addEventListener('click', function() {
                document.getElementById('confirm-cancel-modal').style.display = 'none';
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
            <li><a href="../Order/order.php">Заказать</a></li>
            <li class="my-orders">
                <a href="my_orders.php" class="active">Мои заказы</a>
                <?php if ($orderCount > 0): ?>
                    <span class="order-count"><?php echo $orderCount; ?></span>
                <?php endif; ?>
            </li>
            <li class="theme-toggle">
                <input type="checkbox" id="theme-switch" class="theme-switch">
                <label for="theme-switch" class="theme-label"></label>
            </li>
        </ul>
    </nav>
    <main>
        <div class="order-container">
            <h2>Мои заказы</h2>
            <table>
                <thead>
                    <tr>
                        <th>Номер заказа</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Дата последнего изменения</th>
                        <th>Срок выполнения</th>
                        <th>Бюджет</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['orderNumber']); ?></td>
                                <td><?php echo htmlspecialchars($order['description']); ?></td>
                                <td><?php echo htmlspecialchars(
                                    $order['status'] === 'in processing' ? 'В обработке' : 
                                    ($order['status'] === 'in progress' ? 'В процессе выполнения' : 
                                    ($order['status'] === 'completed' ? 'Завершен' : 
                                    ($order['status'] === 'canceled' ? 'Отменен' : 'Неизвестный статус')))
                                ); ?></td>
                                <td><?php echo htmlspecialchars($order['createdAt']); ?></td>
                                <td><?php echo htmlspecialchars($order['updatedAt']); ?></td>
                                <td><?php echo htmlspecialchars($order['dueDate']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($order['price'], 2, '.', '')); ?> тг</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">У вас нет заказов.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="correction">
                <p>*Заказ можно отменить только со статусом "В обработке". В иных ситуациях обратитесь по телефону</p>
            </div>
            <div class="order-cancel-section">
                <label for="order-select">Выберите заказ для отмены:</label>
                <select id="order-select">
                    <?php foreach ($validOrders as $validOrder): ?>
                        <option value="<?php echo htmlspecialchars($validOrder); ?>"><?php echo htmlspecialchars($validOrder); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="cancel-order-button" class="cancel-button">Отменить</button>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <p>ТOO "Ant Nomad" © 2016-2024. Все права защищены.</p>
            <p>Работаем с 2016 года, предоставляем высококачественные услуги по разработке ПО и консультированию</п>
        </div>
    </footer>
    <!-- Модальное окно для подтверждения отмены заказа -->
    <div id="confirm-cancel-modal" class="cancel-modal">
        <div class="cancel-modal-content">
            <span id="cancel-modal-close" class="cancel-modal-close">&times;</span>
            <p>Вы уверены, что хотите отменить заказ под номером <span id="confirm-order-id"></span>?</p>
            <button id="confirm-cancel-button" class="confirm-cancel-button">Отменить заказ</button>
            <button id="cancel-modal-cancel" class="cancel-modal-cancel">Отмена</button>
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

        document.getElementById('theme-switch').addEventListener('change', function() {
            document.body.classList.toggle('dark-theme');
        });
    </script>
</body>
</html>
