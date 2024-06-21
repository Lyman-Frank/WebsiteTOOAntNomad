<?php
include('../auth_check.php');
include('../db_connect.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userID = $_SESSION['user_id'];
    $projectName = $_POST['projectName'];
    $projectDescription = $_POST['projectDescription'];
    $deadline = $_POST['deadline'];
    $budget = $_POST['budget'];

    // Определение номера заказа для клиента
    $orderNumberQuery = $conn->prepare("SELECT IFNULL(MAX(OrderNumber), 0) + 1 FROM Orders WHERE UserID = ?");
    $orderNumberQuery->bind_param("i", $userID);
    $orderNumberQuery->execute();
    $orderNumberQuery->bind_result($orderNumber);
    $orderNumberQuery->fetch();
    $orderNumberQuery->close();

    // Вставка нового заказа
    $stmt = $conn->prepare("INSERT INTO Orders (UserID, OrderNumber, Description, Status, DueDate, Price) VALUES (?, ?, ?, 'in processing', ?, ?)");
    $stmt->bind_param("iissd", $userID, $orderNumber, $projectDescription, $deadline, $budget);

    if ($stmt->execute()) {
        header("Location: ../My_Orders/my_orders.php");
        exit();
    } else {
        echo "Ошибка: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
