<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/includes/functions.php';

// Получаем все маршруты
$scheduleRoutes = getSchedule();

// Фильтрация по направлению
$filterFrom = $_GET['from'] ?? '';
$filterTo = $_GET['to'] ?? '';
$filterTrainType = $_GET['train_type'] ?? '';

$filteredRoutes = $scheduleRoutes;

if ($filterFrom) {
    $filteredRoutes = array_filter($filteredRoutes, function($route) use ($filterFrom) {
        return stripos($route['from_city'], $filterFrom) !== false;
    });
}

if ($filterTo) {
    $filteredRoutes = array_filter($filteredRoutes, function($route) use ($filterTo) {
        return stripos($route['to_city'], $filterTo) !== false;
    });
}

if ($filterTrainType && $filterTrainType !== 'all') {
    $filteredRoutes = array_filter($filteredRoutes, function($route) use ($filterTrainType) {
        return $route['train_type'] === $filterTrainType;
    });
}

// Сортируем по времени отправления
usort($filteredRoutes, function($a, $b) {
    return strcmp($a['departure_time'], $b['departure_time']);
});

// Получаем список всех станций
$stations = getStations();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание поездов - Местный Экспресс</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-red: #D32F2F;
            --primary-red-dark: #B71C1C;
            --primary-blue: #1E3A5F;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-600: #475569;
            --gray-700: #334155;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Навигация */
        .navbar {
            background: white;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--gray-200);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 16px 0;
            gap: 16px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo i {
            font-size: 28px;
            color: var(--primary-red);
        }

        .logo span {
            font-weight: 800;
            font-size: 1.55rem;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 28px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            font-weight: 500;
            color: var(--gray-600);
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary-red);
        }

        .btn-outline-nav {
            border: 1.5px solid var(--gray-200);
            padding: 8px 18px;
            border-radius: 9999px;
        }

        /* Заголовок */
        .page-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-red-dark));
            padding: 60px 0 40px;
            color: white;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .page-header h1 i {
            margin-right: 12px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Фильтры */
        .filters-section {
            background: white;
            padding: 24px 0;
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 80px;
            z-index: 90;
        }

        .filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--gray-600);
            text-transform: uppercase;
        }

        .filter-group select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1.5px solid var(--gray-200);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
        }

        .btn-filter {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-filter:hover {
            background: var(--primary-red-dark);
        }

        .btn-reset {
            background: var(--gray-200);
            color: var(--gray-700);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        /* Таблица */
        .schedule-section {
            padding: 48px 0;
            flex: 1;
        }

        .results-info {
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #EFF6FF;
            border-radius: 12px;
            color: var(--primary-blue);
        }

        .schedule-table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            background: #FFEBEE;
            font-weight: 700;
            color: var(--primary-red-dark);
        }

        tr:hover {
            background: var(--gray-50);
        }

        .train-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .train-express { background: #FFEBEE; color: #D32F2F; }
        .train-fast { background: #E3F2FD; color: #1565C0; }
        .train-electric { background: #E8F5E9; color: #2E7D32; }

        .btn-buy {
            background: var(--primary-red);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .btn-buy:hover {
            background: var(--primary-red-dark);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 24px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #CBD5E1;
            margin-bottom: 20px;
        }

        /* Футер */
        .footer {
            background: #0A0E17;
            color: #94A3B8;
            margin-top: auto;
        }

        .footer .container {
            padding: 48px 24px 24px;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col {
            flex: 1;
            min-width: 180px;
        }

        .footer-col .logo span {
            color: white !important;
            background: none;
            font-size: 1.3rem;
        }

        .footer-description {
            font-size: 0.85rem;
            max-width: 240px;
        }

        .footer-col h4 {
            color: white;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #94A3B8;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .footer-links a:hover {
            color: #D32F2F;
        }

        .footer-contact p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-contact i {
            width: 20px;
            color: #D32F2F;
        }

        .social-icons {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #1E293B;
            border-radius: 50%;
            color: #94A3B8;
            text-decoration: none;
        }

        .social-icons a:hover {
            background: #D32F2F;
            color: white;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #1E293B;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .container { padding: 0 16px; }
            .nav-container { flex-direction: column; text-align: center; }
            .filters-form { flex-direction: column; }
            .btn-filter, .btn-reset { width: 100%; text-align: center; }
            .footer-content { flex-direction: column; text-align: center; }
            .footer-col .logo { justify-content: center; }
            .footer-description { max-width: 100%; margin: 0 auto; }
            .footer-contact p { justify-content: center; }
            .social-icons { justify-content: center; }
        }
    </style>
</head>
<body>

<!-- Навигация -->
<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-train"></i>
            <span>МЕСТНЫЙЭКСПРЕСС</span>
        </a>
        <div class="nav-links">
            <a href="index.php">Главная</a>
            <a href="index.php#advantages">Преимущества</a>
            <a href="schedule.php">Расписание</a>
            <a href="my-tickets.php" class="btn-outline-nav"><i class="fas fa-ticket-alt"></i> Мои билеты</a>
        </div>
    </div>
</nav>

<!-- Заголовок -->
<section class="page-header">
    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i> Расписание поездов</h1>
        <p>Актуальное расписание пригородных и региональных поездов</p>
    </div>
</section>

<!-- Фильтры -->
<section class="filters-section">
    <div class="container">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group">
                <label><i class="fas fa-map-marker-alt"></i> Откуда</label>
                <select name="from">
                    <option value="">Все станции</option>
                    <?php foreach($stations as $station): ?>
                    <option value="<?= htmlspecialchars($station['city']) ?>" <?= $filterFrom == $station['city'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($station['city']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-flag-checkered"></i> Куда</label>
                <select name="to">
                    <option value="">Все станции</option>
                    <?php foreach($stations as $station): ?>
                    <option value="<?= htmlspecialchars($station['city']) ?>" <?= $filterTo == $station['city'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($station['city']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-train"></i> Тип поезда</label>
                <select name="train_type">
                    <option value="all" <?= $filterTrainType == 'all' ? 'selected' : '' ?>>Все типы</option>
                    <option value="express" <?= $filterTrainType == 'express' ? 'selected' : '' ?>>🚄 Экспресс</option>
                    <option value="fast" <?= $filterTrainType == 'fast' ? 'selected' : '' ?>>⚡ Скорый</option>
                    <option value="electric" <?= $filterTrainType == 'electric' ? 'selected' : '' ?>>🚈 Электричка</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Применить</button>
            <a href="schedule.php" class="btn-reset"><i class="fas fa-undo"></i> Сбросить</a>
        </form>
    </div>
</section>

<!-- Таблица -->
<section class="schedule-section">
    <div class="container">
        <div class="results-info">
            <i class="fas fa-chart-line"></i> Найдено маршрутов: <strong><?= count($filteredRoutes) ?></strong>
        </div>

        <?php if (!empty($filteredRoutes)): ?>
        <div class="schedule-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Направление</th>
                        <th>Отправление</th>
                        <th>Прибытие</th>
                        <th>В пути</th>
                        <th>Тип поезда</th>
                        <th>Цена</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($filteredRoutes as $route): 
                        $trainClass = '';
                        $trainText = '';
                        if($route['train_type'] == 'express') {
                            $trainClass = 'train-express';
                            $trainText = ' Экспресс';
                        } elseif($route['train_type'] == 'fast') {
                            $trainClass = 'train-fast';
                            $trainText = ' Скорый';
                        } else {
                            $trainClass = 'train-electric';
                            $trainText = ' Электричка';
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($route['from_city']) ?></strong>
                            <i class="fas fa-arrow-right" style="color: #D32F2F; margin: 0 8px;"></i>
                            <strong><?= htmlspecialchars($route['to_city']) ?></strong>
                        </td>
                        <td><strong><?= date('H:i', strtotime($route['departure_time'])) ?></strong></td>
                        <td><?= date('H:i', strtotime($route['arrival_time'])) ?></td>
                        <td><?= floor($route['travel_duration']/60) ?>ч <?= $route['travel_duration']%60 ?>мин</td>
                        <td><span class="train-badge <?= $trainClass ?>"><?= $trainText ?></span></td>
                        <td><strong style="color: #D32F2F; font-size: 1.1rem;"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</strong></td>
                        <td><a href="booking.php?schedule_id=<?= $route['schedule_id'] ?>" class="btn-buy">Купить</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Маршруты не найдены</h3>
            <p>Попробуйте изменить параметры поиска</p>
            <a href="schedule.php" class="btn-filter" style="display: inline-block; margin-top: 20px;">Показать все</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Футер -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-col">
                <a href="index.php" class="logo">
                    <i class="fas fa-train"></i>
                    <span>МЕСТНЫЙЭКСПРЕСС</span>
                </a>
                <p class="footer-description">Билеты на пригородные поезда и региональные перевозки.</p>
            </div>
            <div class="footer-col">
                <h4>Пассажирам</h4>
                <ul class="footer-links">
                    <li><a href="#">Правила перевозки</a></li>
                    <li><a href="#">Вернуть билет</a></li>
                    <li><a href="#">Акции и скидки</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Контакты</h4>
                <div class="footer-contact">
                    <p><i class="fas fa-phone"></i> 8-919-146-46-48</p>
                    <p><i class="fas fa-envelope"></i> support@localrail.ru</p>
                </div>
            </div>
            <div class="footer-col">
                <h4>Мы в соцсетях</h4>
                <div class="social-icons">
                    <a href="https://vk.com/ddmolkina"><i class="fab fa-vk"></i></a>
                    <a href="https://t.me/molkina"><i class="fab fa-telegram"></i></a>
                    <a href="https://youtube.com/@sleep1ng169"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            © 2026 Местные железнодорожные перевозки. Все права защищены.
        </div>
    </div>
</footer>

</body>
</html>