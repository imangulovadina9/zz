<?php
require_once __DIR__ . '/db.php';

// Генерация уникального номера билета
function generateBookingNumber() {
    return 'RZD' . date('Ymd') . rand(10000, 99999);
}

// Поиск прямых маршрутов (ИСПРАВЛЕНО)
function searchRoutes($fromStation, $toStation, $date) {
    $db = getDB();
    
    // Экранируем спецсимволы
    $fromLike = '%' . $fromStation . '%';
    $toLike = '%' . $toStation . '%';
    
    $sql = "SELECT * FROM v_available_routes 
            WHERE (from_city LIKE ? OR from_station LIKE ?)
            AND (to_city LIKE ? OR to_station LIKE ?)
            AND travel_date = ?
            ORDER BY departure_time";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$fromLike, $fromLike, $toLike, $toLike, $date]);
    
    return $stmt->fetchAll();
}

// Поиск маршрутов с пересадкой (ИСПРАВЛЕНО)
function searchRoutesWithTransfer($fromStation, $toStation, $date) {
    $db = getDB();
    $transfers = [];
    
    // Получаем ID станций
    $fromLike = '%' . $fromStation . '%';
    $toLike = '%' . $toStation . '%';
    
    // Ищем возможные станции для пересадки
    $sql = "SELECT DISTINCT s.id, s.name, s.city 
            FROM stations s
            WHERE s.city LIKE ? OR s.name LIKE ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$fromLike, $toLike]);
    $stations_list = $stmt->fetchAll();
    
    // Получаем все возможные пересадки
    foreach ($stations_list as $station) {
        // Первый сегмент
        $sql1 = "SELECT * FROM v_available_routes 
                 WHERE (from_city LIKE ? OR from_station LIKE ?)
                 AND (to_city LIKE ? OR to_station LIKE ?)
                 AND travel_date = ?
                 ORDER BY departure_time LIMIT 3";
        
        $stmt1 = $db->prepare($sql1);
        $stmt1->execute([$fromLike, $fromLike, '%' . $station['name'] . '%', '%' . $station['name'] . '%', $date]);
        $firstSegments = $stmt1->fetchAll();
        
        // Второй сегмент
        $sql2 = "SELECT * FROM v_available_routes 
                 WHERE (from_city LIKE ? OR from_station LIKE ?)
                 AND (to_city LIKE ? OR to_station LIKE ?)
                 AND travel_date = ?
                 ORDER BY departure_time LIMIT 3";
        
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute(['%' . $station['name'] . '%', '%' . $station['name'] . '%', $toLike, $toLike, $date]);
        $secondSegments = $stmt2->fetchAll();
        
        // Комбинируем
        foreach ($firstSegments as $first) {
            foreach ($secondSegments as $second) {
                $arrivalTime = strtotime($first['arrival_time']);
                $departureTime = strtotime($second['departure_time']);
                $waitTime = ($departureTime - $arrivalTime) / 60;
                
                if ($waitTime >= 20 && $waitTime <= 720) {
                    $transfers[] = [
                        'transfer_station' => $station['name'],
                        'transfer_city' => $station['city'],
                        'first_segment' => $first,
                        'second_segment' => $second,
                        'total_price' => $first['price'] + $second['price'],
                        'total_duration' => $first['travel_duration'] + $second['travel_duration'] + $waitTime,
                        'wait_time' => $waitTime
                    ];
                }
            }
        }
    }
    
    // Сортируем по цене
    usort($transfers, function($a, $b) {
        return $a['total_price'] <=> $b['total_price'];
    });
    
    return $transfers;
}

// Поиск городов для автодополнения
function searchCities($query) {
    $db = getDB();
    $like = '%' . $query . '%';
    $sql = "SELECT DISTINCT city, name as station, region 
            FROM stations 
            WHERE city LIKE ? OR name LIKE ?
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute([$like, $like]);
    return $stmt->fetchAll();
}

// Получение информации о маршруте по ID
function getRouteById($scheduleId) {
    $db = getDB();
    $sql = "SELECT * FROM v_available_routes WHERE schedule_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$scheduleId]);
    return $stmt->fetch();
}

// Бронирование билета
function bookTicket($scheduleId, $passengerName, $passengerPhone, $userId = null) {
    $db = getDB();
    $db->beginTransaction();
    
    try {
        $checkSql = "SELECT seats_available FROM schedule WHERE id = ? FOR UPDATE";
        $stmt = $db->prepare($checkSql);
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();
        
        if (!$schedule || $schedule['seats_available'] <= 0) {
            throw new Exception("Нет свободных мест");
        }
        
        $priceSql = "SELECT COALESCE(s.price_override, r.price) as price 
                     FROM schedule s 
                     JOIN routes r ON s.route_id = r.id 
                     WHERE s.id = ?";
        $stmt = $db->prepare($priceSql);
        $stmt->execute([$scheduleId]);
        $result = $stmt->fetch();
        $price = $result ? $result['price'] : 0;
        
        $bookingNumber = generateBookingNumber();
        $insertSql = "INSERT INTO tickets (booking_number, user_id, schedule_id, passenger_name, passenger_phone, ticket_price, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'booked')";
        $stmt = $db->prepare($insertSql);
        $stmt->execute([$bookingNumber, $userId, $scheduleId, $passengerName, $passengerPhone, $price]);
        
        $updateSql = "UPDATE schedule SET seats_available = seats_available - 1 WHERE id = ?";
        $stmt = $db->prepare($updateSql);
        $stmt->execute([$scheduleId]);
        
        $db->commit();
        return ['success' => true, 'booking_number' => $bookingNumber, 'price' => $price];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Получение популярных маршрутов
function getPopularRoutes() {
    $db = getDB();
    $sql = "SELECT DISTINCT from_city, to_city, MIN(price) as price 
            FROM v_available_routes 
            WHERE travel_date >= CURDATE()
            GROUP BY from_city, to_city
            LIMIT 6";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

// Получение списка станций/городов
function getStations() {
    $db = getDB();
    $sql = "SELECT DISTINCT id, city, name, region FROM stations ORDER BY city";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

function getSchedule() {
    // Массив с разными уникальными маршрутами
    $uniqueRoutes = [
        ['from_city' => 'Уфа', 'to_city' => 'Белорецк', 'departure_time' => '05:00:00', 'arrival_time' => '13:30:00', 'travel_duration' => 510, 'train_type' => 'fast', 'price' => 1450, 'schedule_id' => 1],
        ['from_city' => 'Орск', 'to_city' => 'Оренбург', 'departure_time' => '06:00:00', 'arrival_time' => '10:30:00', 'travel_duration' => 270, 'train_type' => 'express', 'price' => 980, 'schedule_id' => 2],
        ['from_city' => 'Стерлитамак', 'to_city' => 'Кумертау', 'departure_time' => '07:15:00', 'arrival_time' => '09:45:00', 'travel_duration' => 150, 'train_type' => 'electric', 'price' => 520, 'schedule_id' => 3],
        ['from_city' => 'Ишимбай', 'to_city' => 'Салават', 'departure_time' => '08:00:00', 'arrival_time' => '08:40:00', 'travel_duration' => 40, 'train_type' => 'electric', 'price' => 210, 'schedule_id' => 4],
        ['from_city' => 'Уфа', 'to_city' => 'Абдулино', 'departure_time' => '09:30:00', 'arrival_time' => '14:10:00', 'travel_duration' => 280, 'train_type' => 'electric', 'price' => 1180, 'schedule_id' => 5],
        ['from_city' => 'Оренбург', 'to_city' => 'Бузулук', 'departure_time' => '10:45:00', 'arrival_time' => '14:15:00', 'travel_duration' => 210, 'train_type' => 'fast', 'price' => 890, 'schedule_id' => 6],
    ];
    
    return $uniqueRoutes;
}

/**
 * Возвращает ВСЕ уникальные маршруты для модального окна (без повторов)
 */
function getAllUniqueRoutes() {
    // Здесь может быть запрос к базе данных
    // Пока возвращаем расширенный массив с разными городами
    
    $allRoutes = [
        ['from_city' => 'Уфа', 'to_city' => 'Белорецк', 'departure_time' => '05:00:00', 'arrival_time' => '13:30:00', 'travel_duration' => 510, 'train_type' => 'fast', 'price' => 1450, 'schedule_id' => 1],
        ['from_city' => 'Уфа', 'to_city' => 'Белорецк', 'departure_time' => '12:00:00', 'arrival_time' => '20:30:00', 'travel_duration' => 510, 'train_type' => 'electric', 'price' => 1250, 'schedule_id' => 7],
        ['from_city' => 'Орск', 'to_city' => 'Оренбург', 'departure_time' => '06:00:00', 'arrival_time' => '10:30:00', 'travel_duration' => 270, 'train_type' => 'express', 'price' => 980, 'schedule_id' => 2],
        ['from_city' => 'Орск', 'to_city' => 'Оренбург', 'departure_time' => '14:00:00', 'arrival_time' => '18:30:00', 'travel_duration' => 270, 'train_type' => 'fast', 'price' => 900, 'schedule_id' => 8],
        ['from_city' => 'Стерлитамак', 'to_city' => 'Кумертау', 'departure_time' => '07:15:00', 'arrival_time' => '09:45:00', 'travel_duration' => 150, 'train_type' => 'electric', 'price' => 520, 'schedule_id' => 3],
        ['from_city' => 'Ишимбай', 'to_city' => 'Салават', 'departure_time' => '08:00:00', 'arrival_time' => '08:40:00', 'travel_duration' => 40, 'train_type' => 'electric', 'price' => 210, 'schedule_id' => 4],
        ['from_city' => 'Уфа', 'to_city' => 'Абдулино', 'departure_time' => '09:30:00', 'arrival_time' => '14:10:00', 'travel_duration' => 280, 'train_type' => 'electric', 'price' => 1180, 'schedule_id' => 5],
        ['from_city' => 'Оренбург', 'to_city' => 'Бузулук', 'departure_time' => '10:45:00', 'arrival_time' => '14:15:00', 'travel_duration' => 210, 'train_type' => 'fast', 'price' => 890, 'schedule_id' => 6],
        ['from_city' => 'Уфа', 'to_city' => 'Оренбург', 'departure_time' => '07:00:00', 'arrival_time' => '15:30:00', 'travel_duration' => 510, 'train_type' => 'express', 'price' => 1850, 'schedule_id' => 9],
        ['from_city' => 'Кумертау', 'to_city' => 'Мелеуз', 'departure_time' => '08:30:00', 'arrival_time' => '09:15:00', 'travel_duration' => 45, 'train_type' => 'electric', 'price' => 180, 'schedule_id' => 10],
        ['from_city' => 'Салават', 'to_city' => 'Стерлитамак', 'departure_time' => '17:00:00', 'arrival_time' => '17:35:00', 'travel_duration' => 35, 'train_type' => 'electric', 'price' => 150, 'schedule_id' => 11],
        ['from_city' => 'Белорецк', 'to_city' => 'Уфа', 'departure_time' => '14:30:00', 'arrival_time' => '22:30:00', 'travel_duration' => 480, 'train_type' => 'fast', 'price' => 1450, 'schedule_id' => 12],
    ];
    
    return $allRoutes;
}

?>