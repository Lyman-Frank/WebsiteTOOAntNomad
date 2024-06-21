<?php
include('../auth_check.php');
include('../db_connect.php'); 

$orderID = $_POST['order_id'];

// Обновление статуса заказа на "Отменен"
$updateQuery = $conn->prepare("UPDATE Orders SET Status = 'canceled' WHERE OrderNumber = ?");
$updateQuery->bind_param("i", $orderID);
if ($updateQuery->execute()) {
    echo "Заказ отменен.";
} else {
    echo "Ошибка при отмене заказа.";
}
$updateQuery->close();
$conn->close();
?>
