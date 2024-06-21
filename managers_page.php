<?php
include('auth_check.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include('db_connect.php'); 

// Получение темы пользователя
$user_id = $_SESSION['user_id']; // Предполагается, что ID пользователя хранится в сессии
$theme_query = "SELECT Theme FROM Users WHERE ID = ?";
$stmt = $conn->prepare($theme_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($theme);
$stmt->fetch();
$stmt->close();

// Получение заказов, где роль пользователя 'client'
$query = "
    SELECT Orders.OrderID, Users.FullName, Orders.OrderNumber, Orders.Description, Orders.Status, Orders.CreatedAt, Orders.UpdatedAt, Orders.DueDate, Orders.Price
    FROM Orders
    JOIN Users ON Orders.UserID = Users.ID
    WHERE Users.Role = 'client'
";
$result = $conn->query($query);

$orders = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$conn->close();

// Функция для перевода статусов
function translateStatus($status) {
    switch ($status) {
        case 'in processing':
            return 'В обработке';
        case 'in progress':
            return 'В процессе выполнения';
        case 'completed':
            return 'Завершен';
        case 'canceled':
            return 'Отменен';
        default:
            return $status;
    }
}

// Функция для обратного перевода статусов
function reverseTranslateStatus($status) {
    switch ($status) {
        case 'В обработке':
            return 'in processing';
        case 'В процессе выполнения':
            return 'in progress';
        case 'Завершен':
            return 'completed';
        case 'Отменен':
            return 'canceled';
        default:
            return $status;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Менеджерская страница</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="managers_styles.css">
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
    <main>
    <!-- Модальное окно для подтверждения выхода -->
    <div id="exit_modal" class="exit_modal">
        <div class="exit_modal-content">
            <span class="exit_modal-close">&times;</span>
            <p>Вы уверены, что хотите выйти с аккаунта?</p>
            <button id="exit_modal-confirm-logout" class="exit_modal-confirm-button">Да</button>
            <button id="exit_modal-cancel-logout" class="exit_modal-cancel-button">Нет</button>
        </div>
    </div>
        <div class="theme-toggle">
            <input type="checkbox" id="theme-switch" class="theme-switch">
                <label for="theme-switch" class="theme-label"></label>
        </div>
        <h2>Заказы клиентов</h2>
        <div class="table-container">
            <table id="orders-table">
                <thead>
                    <tr>
                        <th>ID Заказа</th>
                        <th>Имя Клиента</th>
                        <th>Номер Заказа</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Дата Создания</th>
                        <th>Дата последнего изменения</th>
                        <th>Срок Выполнения</th>
                        <th>Цена</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr data-order-id="<?php echo htmlspecialchars($order['OrderID']); ?>" data-order='<?php echo json_encode($order); ?>'>
                                <td><?php echo htmlspecialchars($order['OrderID']); ?></td>
                                <td><?php echo htmlspecialchars($order['FullName']); ?></td>
                                <td><?php echo htmlspecialchars($order['OrderNumber']); ?></td>
                                <td><?php echo htmlspecialchars($order['Description']); ?></td>
                                <td><?php echo htmlspecialchars(translateStatus($order['Status'])); ?></td>
                                <td><?php echo htmlspecialchars($order['CreatedAt']); ?></td>
                                <td><?php echo htmlspecialchars($order['UpdatedAt']); ?></td>
                                <td><?php echo htmlspecialchars($order['DueDate']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($order['Price'], 2, '.', '')); ?> тг</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">Нет заказов клиентов.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <form id="order-form">
            <label for="order-select">Выберите ID заказа для изменения:</label>
            <select id="order-select">
                <?php foreach ($orders as $order): ?>
                    <option value="<?php echo htmlspecialchars($order['OrderID']); ?>"><?php echo htmlspecialchars($order['OrderID']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="edit-button">Изменить</button>
            <button type="button" id="filter-button">Фильтр</button>
            <button type="button" id="export-button">Экспорт в Excel</button>
        </form>
        <div id="filter-container" class="filter-container" style="display: none;">
            <div class="filter-container-horizontal">
                <label for="filter-select">Фильтровать по:</label>
                <select id="filter-select">
                    <option value="status">По статусу</option>
                    <option value="price">По цене</option>
                </select>
                <select id="filter-value">
                    <option value="in processing">В обработке</option>
                    <option value="in progress">В процессе выполнения</option>
                    <option value="completed">Завершен</option>
                    <option value="canceled">Отменен</option>
                </select>
            </div>
            <button type="button" id="apply-filter">Фильтровать</button>
        </div>
    </main>
                    
    <!-- Модальное окно -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="edit-order-form" action="update_order.php" method="post">
                <input type="hidden" id="order-id" name="orderID">
                <div>
                    <label for="orderID">ID заказа:</label>
                    <input type="text" id="orderID" name="orderID_display" readonly>
                </div>
                <div>
                    <label for="fullName">Имя клиента:</label>
                    <input type="text" id="fullName" name="fullName" readonly>
                </div>
                <div>
                    <label for="orderNumber">Номер заказа:</label>
                    <input type="text" id="orderNumber" name="orderNumber" readonly>
                </div>
                <div>
                    <label for="status">Статус:</label>
                    <select id="status" name="status">
                        <option value="in processing">В обработке</option>
                        <option value="in progress">В процессе выполнения</option>
                        <option value="completed">Завершен</option>
                        <option value="canceled">Отменен</option>
                    </select>
                </div>
                <div>
                    <label for="dueDate">Дата окончание проекта:</label>
                    <input type="date" id="dueDate" name="dueDate">
                </div>
                <div>
                    <label for="price">Цена (₸):</label>
                    <input type="number" step="500" id="price" name="price">
                </div>
                <button type="button" id="confirm-button">Подтвердить</button>
            </form>
        </div>
    </div>

    <!-- Контейнер для ошибок -->
    <div id="error-modal" class="modal">
        <div class="modal-content">
            <span class="close-error">&times;</span>
            <div id="error-message"></div>
            <button id="error-ok-button">Ок</button>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>ТOO "Ant Nomad" © 2016-2024. Все права защищены.</p>
            <p>Работаем с 2016 года, предоставляем высококачественные услуги по разработке ПО и консультированию</p>
        </div>
    </footer>
    
    <script>
        // Скрипт для открытия модального окна и заполнения формы
        document.getElementById('edit-button').addEventListener('click', function() {
            var orderID = document.getElementById('order-select').value;
            var orderElement = document.querySelector('tr[data-order-id="' + orderID + '"]');
            var order = JSON.parse(orderElement.dataset.order);

            document.getElementById('order-id').value = order.OrderID;
            document.getElementById('orderID').value = order.OrderID;
            document.getElementById('fullName').value = order.FullName;
            document.getElementById('orderNumber').value = order.OrderNumber;
            document.getElementById('status').value = order.Status;
            document.getElementById('dueDate').value = order.DueDate;
            document.getElementById('price').value = order.Price;

            document.getElementById('modal').style.display = 'block';
        });

        // Закрытие модального окна
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('modal').style.display = 'none';
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('modal')) {
                document.getElementById('modal').style.display = 'none';
            }
            if (event.target == document.getElementById('error-modal')) {
                document.getElementById('error-modal').style.display = 'none';
            }
        };

        // Закрытие окна ошибок
        document.querySelector('.close-error').addEventListener('click', function() {
            document.getElementById('error-modal').style.display = 'none';
        });

        document.getElementById('error-ok-button').addEventListener('click', function() {
            document.getElementById('error-modal').style.display = 'none';
        });

        // Проверки при нажатии на кнопку "Подтвердить"
        document.getElementById('confirm-button').addEventListener('click', function() {
            var dueDate = document.getElementById('dueDate').value;
            var price = parseFloat(document.getElementById('price').value);
            var currentDate = new Date().toISOString().split('T')[0];
            var maxPrice = 1000000; // Максимальная цена, например, 1 000 000
            var maxYear = 2050;

            if (!dueDate) {
                showError("Срок выполнения не может быть пустым.");
                return;
            }

            if (new Date(dueDate) < new Date(currentDate)) {
                showError("Срок выполнения не может быть раньше текущей даты.");
                return;
            }

            if (new Date(dueDate).getFullYear() > maxYear) {
                showError("Срок выполнения не может быть позже 2050 года.");
                return;
            }

            if (isNaN(price)) {
                showError("Цена не может быть пустой.");
                return;
            }

            if (price > maxPrice) {
                showError("Цена не может превышать " + maxPrice + " ₸.");
                return;
            }

            // Если все проверки пройдены, отправляем форму
            document.getElementById('edit-order-form').submit();
        });

        function showError(message) {
            document.getElementById('error-message').innerText = message;
            document.getElementById('error-modal').style.display = 'block';
        }

        // Скрипт для фильтрации
        document.getElementById('filter-button').addEventListener('click', function() {
            var filterContainer = document.getElementById('filter-container');
            if (filterContainer.style.display === 'none') {
                filterContainer.style.display = 'block';
                document.getElementById('filter-button').textContent = 'Закрыть';
            } else {
                filterContainer.style.display = 'none';
                document.getElementById('filter-button').textContent = 'Фильтр';
                resetTable();
            }
        });

        document.getElementById('filter-select').addEventListener('change', function() {
            var filterSelect = document.getElementById('filter-select').value;
            var filterValue = document.getElementById('filter-value');

            filterValue.innerHTML = '';

            if (filterSelect === 'status') {
                filterValue.innerHTML = `
                    <option value="in processing">В обработке</option>
                    <option value="in progress">В процессе выполнения</option>
                    <option value="completed">Завершен</option>
                    <option value="canceled">Отменен</option>
                `;
            } else if (filterSelect === 'price') {
                filterValue.innerHTML = `
                    <option value="asc">По увеличению</option>
                    <option value="desc">По уменьшению</option>
                `;
            }

            filterValue.style.display = 'block';
        });

        document.getElementById('apply-filter').addEventListener('click', function() {
            var filterSelect = document.getElementById('filter-select').value;
            var filterValue = document.getElementById('filter-value').value;
            var rows = document.querySelectorAll('#orders-table tbody tr');

            rows.forEach(function(row) {
                row.style.display = 'table-row';
            });

            if (filterSelect === 'status') {
                rows.forEach(function(row) {
                    var status = row.querySelector('td:nth-child(5)').textContent;
                    if (translateStatus(filterValue) !== status) {
                        row.style.display = 'none';
                    }
                });
            } else if (filterSelect === 'price') {
                var sortedRows = Array.from(rows).sort(function(a, b) {
                    var priceA = parseFloat(a.querySelector('td:nth-child(9)').textContent.replace(' тг', '').replace(',', ''));
                    var priceB = parseFloat(b.querySelector('td:nth-child(9)').textContent.replace(' тг', '').replace(',', ''));

                    return filterValue === 'asc' ? priceA - priceB : priceB - priceA;
                });

                var tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = '';
                sortedRows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }
        });

        function resetTable() {
            var rows = document.querySelectorAll('#orders-table tbody tr');
            rows.forEach(function(row) {
                row.style.display = 'table-row';
            });
        }

        function translateStatus(status) {
            switch (status) {
                case 'in processing':
                    return 'В обработке';
                case 'in progress':
                    return 'В процессе выполнения';
                case 'completed':
                    return 'Завершен';
                case 'canceled':
                    return 'Отменен';
                default:
                    return status;
            }
        }
        document.getElementById('theme-switch').addEventListener('change', function() {
            document.body.classList.toggle('dark-theme');
        });

        // Экспорт Excel
        document.getElementById('export-button').addEventListener('click', function() {
            var table = document.getElementById('orders-table');
            var rows = table.querySelectorAll('tr');
            var data = [];
            
            rows.forEach(function(row) {
                if (row.style.display !== 'none') {  // Только видимые строки
                    var cells = row.querySelectorAll('th, td');
                    var rowContent = [];
                    cells.forEach(function(cell) {
                        rowContent.push(cell.innerText.replace(/\n/g, ' '));
                    });
                    data.push(rowContent);
                }
            });

            var ws = XLSX.utils.aoa_to_sheet(data);
            
            // Установка ширины столбцов
            var wscols = data[0].map((_, i) => {
                if (i === 3) {
                    // Ширина столбца "Описание" (индекс 3)
                    return { wpx: 100 };
                } else {
                    // Подгонка ширины столбцов по содержимому
                    var maxLength = Math.max(...data.map(row => (row[i] || '').toString().length));
                    return { wch: maxLength + 2 }; // +2 для небольшого запаса
                }
            });

            ws['!cols'] = wscols;

            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Orders');
            XLSX.writeFile(wb, 'orders.xlsx');
        });

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
    </script>
</body>
</html>
