<?php
session_start();
include('db_connect.php'); 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT ID, Password, Role FROM Users WHERE Login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userID, $hashedPassword, $role);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['user_id'] = $userID;
            $_SESSION['role'] = $role;
            echo json_encode(['success' => true, 'role' => $role]);
        } else {
            echo json_encode(['success' => false, 'message' => "Неверный пароль."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Пользователь с таким логином не найден."]);
    }

    $stmt->close();
    $conn->close();
}
?>
