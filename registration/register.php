<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Проверки на серверной стороне
    if (!preg_match("/^[A-Zz-А-Яя\s]+$/", $fullname)) {
        echo json_encode(['success' => false, 'message' => "Полное имя может содержать только буквы."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => "Введите корректный email."]);
        exit;
    }

    if (!preg_match("/^\+?[0-9\s\-]{10,20}$/", $phone)) {
        echo json_encode(['success' => false, 'message' => "Введите корректный номер телефона."]);
        exit;
    }

    $dobDate = date_create($dob);
    if (!$dobDate) {
        echo json_encode(['success' => false, 'message' => "Введите корректную дату рождения."]);
        exit;
    }

    $age = date_diff(date_create($dob), date_create('now'))->y;
    if ($age < 18 || $age > 100) {
        echo json_encode(['success' => false, 'message' => "Возраст должен быть не менее 18 лет и не более 100 лет."]);
        exit;
    }

    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => "Пароли не совпадают."]);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => "Пароль должен быть не менее 6 символов."]);
        exit;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        echo json_encode(['success' => false, 'message' => "Пароль должен содержать хотя бы одну заглавную букву."]);
        exit;
    }

    if (!preg_match('/[a-z]/', $password)) {
        echo json_encode(['success' => false, 'message' => "Пароль должен содержать хотя бы одну строчную букву."]);
        exit;
    }

    if (!preg_match('/[0-9]/', $password)) {
        echo json_encode(['success' => false, 'message' => "Пароль должен содержать хотя бы одну цифру."]);
        exit;
    }

    if (!preg_match('/[!@#$%^&*(),.?":{}|<>_-]/', $password)) {
        echo json_encode(['success' => false, 'message' => "Пароль должен содержать хотя бы один специальный символ."]);
        exit;
    }

    include('../db_connect.php'); 

    // Проверка уникальности email и телефона
    $stmt = $conn->prepare("SELECT ID FROM Users WHERE Email = ? OR PhoneNumber = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Пользователь с таким email или номером телефона уже существует."]);
        exit;
    }

    $stmt->close();

    // Хэширование пароля
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Добавление нового пользователя
    $stmt = $conn->prepare("INSERT INTO Users (FullName, Email, PhoneNumber, Login, Password, Role, DateOfBirth, Address, Status) VALUES (?, ?, ?, ?, ?, 'client', ?, ?, 'inactive')");
    $stmt->bind_param("sssssss", $fullname, $email, $phone, $email, $hashedPassword, $dob, $address);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Регистрация успешна."]);
    } else {
        echo json_encode(['success' => false, 'message' => "Ошибка: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
