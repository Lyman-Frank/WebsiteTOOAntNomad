<?php
include('auth_check.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include('db_connect.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];
    $status = $_POST['status'];
    $dueDate = $_POST['dueDate'];
    $price = $_POST['price'];

    $stmt = $conn->prepare("UPDATE Orders SET Status = ?, DueDate = ?, Price = ? WHERE OrderID = ?");
    $stmt->bind_param("ssdi", $status, $dueDate, $price, $orderID);

    if ($stmt->execute()) {
        echo "Заказ успешно обновлен!";
    } else {
        echo "Ошибка: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
header("Location: managers_page.php");
exit();
?>
