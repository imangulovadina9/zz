<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, авторизуйтесь для покупки билета']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $train_id = $_POST['train_id'] ?? 0;
    $from_station_id = $_POST['from_station_id'] ?? 0;
    $to_station_id = $_POST['to_station_id'] ?? 0;
    $passenger_name = trim($_POST['passenger_name'] ?? '');
    $passport_number = trim($_POST['passport_number'] ?? '');
    $carriage_type = $_POST['carriage_type'] ?? 'Плацкарт';
    
    // Простая валидация
    if (!$train_id || !$from_station_id || !$to_station_id || !$passenger_name || !$passport_number) {
        echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
        exit;
    }
    
    // Рассчитываем цену (упрощённо)
    $prices = [
        'Плацкарт' => 2100,
        'Купе' => 3800,
        'Люкс' => 7200
    ];
    $price = $prices[$carriage_type] ?? 2100;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tickets (user_id, train_id, from_station_id, to_station_id, passenger_name, passport_number, carriage_type, price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $train_id,
            $from_station_id,
            $to_station_id,
            $passenger_name,
            $passport_number,
            $carriage_type,
            $price
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Билет успешно оформлен! Электронный билет отправлен на почту.',
            'redirect' => 'profile.php'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при оформлении билета: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}

if ($from_station_id == $to_station_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Станции отправления и прибытия должны отличаться'
    ]);
    exit;
}