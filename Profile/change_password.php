<?php
include('../auth_check.php');
include('../db_connect.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userID = $_SESSION['user_id'];
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmNewPassword = $_POST['confirm_new_password'];

    if ($newPassword !== $confirmNewPassword) {
        echo json_encode(array('success' => false, 'message' => "Новый пароль и подтверждение пароля не совпадают."));
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(array('success' => false, 'message' => "Пароль должен быть не менее 6 символов."));
        exit;
    }

    if (!preg_match('/[A-Z]/', $newPassword)) {
        echo json_encode(array('success' => false, 'message' => "Пароль должен содержать хотя бы одну заглавную букву."));
        exit;
    }

    if (!preg_match('/[a-z]/', $newPassword)) {
        echo json_encode(array('success' => false, 'message' => "Пароль должен содержать хотя бы одну строчную букву."));
        exit;
    }

    if (!preg_match('/[0-9]/', $newPassword)) {
        echo json_encode(array('success' => false, 'message' => "Пароль должен содержать хотя бы одну цифру."));
        exit;
    }

    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
        echo json_encode(array('success' => false, 'message' => "Пароль должен содержать хотя бы один специальный символ."));
        exit;
    }

    $stmt = $conn->prepare("SELECT Password FROM Users WHERE ID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($oldPassword, $hashedPassword)) {
        echo json_encode(array('success' => false, 'message' => "Старый пароль неверен."));
        exit;
    }

    if (password_verify($newPassword, $hashedPassword)) {
        echo json_encode(array('success' => false, 'message' => "Новый пароль не должен совпадать со старым."));
        exit;
    }

    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE Users SET Password = ? WHERE ID = ?");
    $stmt->bind_param("si", $newHashedPassword, $userID);

    if ($stmt->execute()) {
        echo json_encode(array('success' => true, 'message' => "Пароль успешно обновлен."));
    } else {
        echo json_encode(array('success' => false, 'message' => "Ошибка: " . $stmt->error));
    }

    $stmt->close();
}

$conn->close();
?>
