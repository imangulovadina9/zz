<?php
session_start();
require_once 'config.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if($stmt->fetch()) {
            $error = 'Email уже зарегистрирован';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            if($stmt->execute([$name, $email, $phone, $hashed_password])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка регистрации';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Вокзал Стерлитамак</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="login-page">
        <div class="login-container">
            <h2><i class="fas fa-user-plus"></i> Регистрация</h2>
            
            <?php if($error): ?>
                <div style="background:#fee; color:#c00; padding:10px; border-radius:8px; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div style="background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:20px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="text" name="name" placeholder="ФИО" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="tel" name="phone" placeholder="Телефон" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
                <button type="submit" class="btn-primary">Зарегистрироваться</button>
            </form>
            
            <div class="login-footer">
                <a href="login.php">Уже есть аккаунт? Войти</a>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>