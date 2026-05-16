<?php
session_start();
require_once 'config.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        header('Location: index.php');
        exit();
    } else {
        $error = 'Неверный email или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Вокзал Стерлитамак</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="login-page">
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Вход в личный кабинет</h2>
            
            <?php if($error): ?>
                <div style="background:#fee; color:#c00; padding:10px; border-radius:8px; margin-bottom:20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль" required>
                <button type="submit" class="btn-primary">Войти</button>
            </form>
            
            <div class="login-footer">
                <a href="register.php">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>