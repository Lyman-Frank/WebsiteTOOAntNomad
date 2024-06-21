<?php
include('auth_check.php');

// Проверка, является ли запрос методом POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include('db_connect.php'); 

    // Обновление темы пользователя
    $user_id = $_SESSION['user_id'];
    $theme = intval($_POST['theme']);
    $update_theme_query = "UPDATE Users SET Theme = ? WHERE ID = ?";
    $stmt = $conn->prepare($update_theme_query);
    $stmt->bind_param("ii", $theme, $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
