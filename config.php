<?php
$host = '127.0.0.1';  // или 'localhost'
$dbname = 'railway_station';
$username = 'root';
$password = '';  // оставьте пустым (у вас нет пароля)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>