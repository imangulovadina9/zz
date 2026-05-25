<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/includes/functions.php';

// Получаем данные
$stations = getStations();
$popularRoutes = getPopularRoutes();
$shortScheduleRoutes = getSchedule(); // 5 уникальных маршрутов для главной
$allScheduleRoutes = getAllUniqueRoutes(); // все маршруты для модального окна

// Обработка поиска
$searchResults = [];
$transferResults = [];
$from = '';
$to = '';
$date = date('Y-m-d');
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $from = trim($_POST['from_station'] ?? '');
    $to = trim($_POST['to_station'] ?? '');
    $date = $_POST['travel_date'] ?? date('Y-m-d');
    
    if ($from && $to) {
        $searchResults = searchRoutes($from, $to, $date);
        
        if (empty($searchResults)) {
            $transferResults = searchRoutesWithTransfer($from, $to, $date);
        }
        
        if (empty($searchResults) && empty($transferResults)) {
            $debug_info = "По запросу '$from' → '$to' на дату $date ничего не найдено. Попробуйте другие города.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Местный Экспресс - билеты на пригородные поезда</title>
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
            --primary-red-light: #FFEBEE;
            --primary-blue: #1E3A5F;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #CBD5E1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0F172A;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.5;
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
            letter-spacing: -0.5px;
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
            color: var(--gray-700);
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary-red);
        }

        .btn-outline-nav {
            border: 1.5px solid var(--gray-300);
            padding: 8px 18px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .btn-outline-nav:hover {
            border-color: var(--primary-red);
            background: var(--primary-red-light);
            color: var(--primary-red) !important;
        }

        /* Hero блок */
        .hero {
            background: linear-gradient(112deg, var(--gray-100) 0%, white 100%);
            padding: 64px 0 80px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .hero-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 48px;
            align-items: center;
            justify-content: space-between;
        }

        .hero-text {
            flex: 1.2;
            min-width: 280px;
        }

        .hero-text h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -1px;
            background: linear-gradient(to right, var(--primary-blue), var(--primary-red-dark));
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 20px;
        }

        .hero-text p {
            font-size: 1.2rem;
            color: var(--gray-600);
            margin-bottom: 28px;
            max-width: 520px;
        }

        .features-mini {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .features-mini div {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.8);
            padding: 8px 18px;
            border-radius: 9999px;
            font-weight: 500;
        }

        .features-mini i {
            color: var(--primary-red);
        }

        /* Форма поиска */
        .booking-card {
            flex: 1;
            min-width: 340px;
            background: white;
            border-radius: 32px;
            box-shadow: var(--shadow-xl);
            padding: 32px 28px;
            transition: transform 0.25s;
        }

        .booking-card:hover {
            transform: translateY(-4px);
        }

        .booking-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-card h3 i {
            color: var(--primary-red);
        }

        .booking-sub {
            color: var(--gray-600);
            margin-bottom: 28px;
            border-left: 3px solid var(--primary-red);
            padding-left: 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .form-group label i {
            margin-right: 6px;
            color: var(--primary-red);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 24px;
            border: 1.5px solid var(--gray-200);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }

        .btn-buy {
            background: var(--primary-red);
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-buy:hover {
            background: var(--primary-red-dark);
            transform: scale(0.98);
        }

        /* Результаты поиска */
        .search-results {
            padding: 60px 0;
            background: white;
        }

        .section-title {
            text-align: center;
            font-size: clamp(1.5rem, 4vw, 2rem);
            font-weight: 800;
            margin-bottom: 48px;
        }

        .section-title span {
            color: var(--primary-red);
        }

        .results-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .result-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.25s;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .result-time {
            display: flex;
            align-items: center;
            gap: 24px;
            flex: 2;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        .btn-select {
            background: var(--primary-red);
            color: white;
            padding: 8px 28px;
            border-radius: 9999px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn-select:hover {
            background: var(--primary-red-dark);
        }

        .debug-info {
            background: #FFF3E0;
            padding: 16px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
            color: #E65100;
        }

        /* Преимущества */
        .advantages {
            padding: 80px 0;
            background: var(--gray-50);
        }

        .cards-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            justify-content: center;
        }

        .advantage-card {
            background: white;
            padding: 32px 24px;
            border-radius: 32px;
            text-align: center;
            flex: 1;
            min-width: 220px;
            transition: all 0.25s;
            border: 1px solid var(--gray-200);
        }

        .advantage-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .advantage-card i {
            font-size: 2.5rem;
            color: var(--primary-red);
            margin-bottom: 20px;
        }

        .advantage-card h4 {
            font-size: 1.4rem;
            margin-bottom: 12px;
        }

        /* Расписание на главной */
        .schedule {
            padding: 70px 0;
            background: white;
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .schedule-header .section-title {
            margin-bottom: 0;
        }

        .btn-full-schedule {
            background: transparent;
            border: 2px solid var(--primary-red);
            color: var(--primary-red);
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-full-schedule:hover {
            background: var(--primary-red);
            color: white;
        }

        .schedule-table-wrapper {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
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

        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 1200px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            padding: 24px;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gray-200);
            position: sticky;
            top: 0;
            background: white;
        }

        .modal-header h2 {
            color: var(--primary-red);
            font-size: 1.8rem;
        }

        .close-modal {
            font-size: 32px;
            cursor: pointer;
            color: var(--gray-600);
            transition: color 0.2s;
        }

        .close-modal:hover {
            color: var(--primary-red);
        }

        /* Футер */
        .footer {
            background: #0A0E17;
            color: #E2E8F0;
            width: 100%;
            margin-top: auto;
        }

        .footer .container {
            max-width: 1280px;
            margin: 0 auto;
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

        .footer-col .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            text-decoration: none;
        }

        .footer-col .logo span {
            color: white !important;
            background: none;
            font-size: 1.3rem;
        }

        .footer-description {
            font-size: 0.85rem;
            color: #94A3B8;
            line-height: 1.5;
            max-width: 240px;
        }

        .footer-col h4 {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #94A3B8;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
            cursor: pointer;
        }

        .footer-links a:hover {
            color: #D32F2F;
        }

        .footer-contact {
            font-size: 0.85rem;
            color: #94A3B8;
            line-height: 1.8;
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
            transition: all 0.2s;
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
            color: #64748B;
        }

        @media (max-width: 768px) {
            .container { padding: 0 16px; }
            .nav-container { flex-direction: column; text-align: center; }
            .hero-grid { flex-direction: column; }
            .hero-text { text-align: center; }
            .features-mini { justify-content: center; }
            .schedule-header { flex-direction: column; gap: 16px; text-align: center; }
            .footer .container { padding: 32px 20px 20px; }
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
            <a href="#hero">Билеты</a>
            <a href="#advantages">Преимущества</a>
            <a href="#schedule">Расписание</a>
            <a href="my-tickets.php" class="btn-outline-nav"><i class="fas fa-ticket-alt"></i> Мои билеты</a>
        </div>
    </div>
</nav>

<!-- Hero блок -->
<section class="hero" id="hero">
    <div class="container hero-grid">
        <div class="hero-text">
            <h1>Билеты на <br>местные поезда</h1>
            <p>Удобные пригородные и региональные маршруты. Электрички, экспрессы — покупайте билеты за пару минут.</p>
            <div class="features-mini">
                <div><i class="fas fa-ticket-alt"></i> <span>Без комиссии</span></div>
                <div><i class="fas fa-clock"></i> <span>Электронный билет</span></div>
                <div><i class="fas fa-wifi"></i> <span>Бесплатный Wi-Fi</span></div>
            </div>
        </div>

        <!-- Форма поиска -->
        <div class="booking-card">
            <h3><i class="fas fa-ticket-alt"></i> Найти билеты</h3>
            <div class="booking-sub">Выберите город отправления и назначения</div>
            <form method="GET" action="search-results.php">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Откуда</label>
                    <input type="text" id="fromStation" name="from" list="citiesList" placeholder="Например: Уфа, Стерлитамак" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-flag-checkered"></i> Куда</label>
                    <input type="text" id="toStation" name="to" list="citiesList" placeholder="Например: Оренбург, Мелеуз" required>
                </div>
                <datalist id="citiesList">
                    <?php foreach($stations as $station): ?>
                    <option value="<?= htmlspecialchars($station['city']) ?>">
                    <?php endforeach; ?>
                </datalist>
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Дата поездки</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="submit" class="btn-buy"><i class="fas fa-search"></i> Найти билеты</button>
            </form>
        </div>
    </div>
</section>

<!-- Результаты поиска -->
<?php if (!empty($searchResults)): ?>
<section class="search-results">
    <div class="container">
        <h2 class="section-title">Доступные <span>рейсы</span></h2>
        <div class="results-list">
            <?php foreach($searchResults as $route): ?>
            <div class="result-card">
                <div class="result-time">
                    <div><strong><?= date('H:i', strtotime($route['departure_time'])) ?></strong><br><small><?= htmlspecialchars($route['from_city']) ?></small></div>
                    <div class="arrow"><i class="fas fa-arrow-right"></i></div>
                    <div><strong><?= date('H:i', strtotime($route['arrival_time'])) ?></strong><br><small><?= htmlspecialchars($route['to_city']) ?></small></div>
                </div>
                <div>
                    <span><?= $route['train_type'] == 'express' ? '🚄 Экспресс' : '🚈 Электричка' ?></span>
                    <span>⏱ <?= floor($route['travel_duration']/60) ?>ч <?= $route['travel_duration']%60 ?>мин</span>
                </div>
                <div class="result-price">
                    <div class="price"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</div>
                    <a href="booking.php?schedule_id=<?= $route['schedule_id'] ?>" class="btn-select">Выбрать</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Отладочное сообщение -->
<?php if (!empty($debug_info)): ?>
<section class="search-results">
    <div class="container">
        <div class="debug-info">
            <i class="fas fa-info-circle"></i> <?= $debug_info ?>
            <br><br>
            <small>Попробуйте: Уфа → Оренбург, или Уфа → Стерлитамак</small>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Преимущества -->
<section class="advantages" id="advantages">
    <div class="container">
        <div class="section-title">Почему <span>местный поезд</span> — ваш выбор</div>
        <div class="cards-grid">
            <div class="advantage-card">
                <i class="fas fa-stopwatch"></i>
                <h4>Точное расписание</h4>
                <p>Отправление без задержек</p>
            </div>
            <div class="advantage-card">
                <i class="fas fa-coins"></i>
                <h4>Доступные цены</h4>
                <p>Скидки и семейные тарифы</p>
            </div>
            <div class="advantage-card">
                <i class="fas fa-charging-station"></i>
                <h4>Эко-транспорт</h4>
                <p>Электропоезда нового поколения</p>
            </div>
            <div class="advantage-card">
                <i class="fas fa-hand-holding-heart"></i>
                <h4>Поддержка 24/7</h4>
                <p>Круглосуточная помощь</p>
            </div>
        </div>
    </div>
</section>

<!-- Краткое расписание на главной -->
<section class="schedule" id="schedule">
    <div class="container">
        <div class="schedule-header">
            <h2 class="section-title">Актуальное <span>расписание</span></h2>
            <button class="btn-full-schedule" onclick="openFullScheduleModal()">
                <i class="fas fa-calendar-alt"></i> Полное расписание
            </button>
        </div>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($shortScheduleRoutes)): ?>
                        <?php foreach($shortScheduleRoutes as $route): 
                            $trainClass = '';
                            $trainText = '';
                            if($route['train_type'] == 'express') {
                                $trainClass = 'train-express';
                                $trainText = '🚄 Экспресс';
                            } elseif($route['train_type'] == 'fast') {
                                $trainClass = 'train-fast';
                                $trainText = '⚡ Скорый';
                            } else {
                                $trainClass = 'train-electric';
                                $trainText = '🚈 Электричка';
                            }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($route['from_city']) ?></strong> → <strong><?= htmlspecialchars($route['to_city']) ?></strong></td>
                            <td><?= date('H:i', strtotime($route['departure_time'])) ?></td>
                            <td><?= date('H:i', strtotime($route['arrival_time'])) ?></td>
                            <td><?= floor($route['travel_duration']/60) ?>ч <?= $route['travel_duration']%60 ?>мин</td>
                            <td><span class="train-badge <?= $trainClass ?>"><?= $trainText ?></span></td>
                            <td><strong style="color: #D32F2F;"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">Нет доступных маршрутов</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                    <li><a onclick="openModal('rules')">Правила перевозки</a></li>
                    <li><a onclick="openModal('refund')">Вернуть билет</a></li>
                    <li><a onclick="openModal('promotions')">Акции и скидки</a></li>
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

<!-- Модальное окно для полного расписания -->
<div id="scheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-calendar-alt"></i> Полное расписание поездов</h2>
            <span class="close-modal" onclick="closeScheduleModal()">&times;</span>
        </div>
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
                    <?php if (!empty($allScheduleRoutes)): ?>
                        <?php foreach($allScheduleRoutes as $route): 
                            $trainClass = '';
                            $trainText = '';
                            if($route['train_type'] == 'express') {
                                $trainClass = 'train-express';
                                $trainText = '🚄 Экспресс';
                            } elseif($route['train_type'] == 'fast') {
                                $trainClass = 'train-fast';
                                $trainText = '⚡ Скорый';
                            } else {
                                $trainClass = 'train-electric';
                                $trainText = '🚈 Электричка';
                            }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($route['from_city']) ?></strong> → <strong><?= htmlspecialchars($route['to_city']) ?></strong></td>
                            <td><?= date('H:i', strtotime($route['departure_time'])) ?></td>
                            <td><?= date('H:i', strtotime($route['arrival_time'])) ?></td>
                            <td><?= floor($route['travel_duration']/60) ?>ч <?= $route['travel_duration']%60 ?>мин</td>
                            <td><span class="train-badge <?= $trainClass ?>"><?= $trainText ?></span></td>
                            <td><strong style="color: #D32F2F;"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</strong></td>
                            <td><a href="booking.php?schedule_id=<?= $route['schedule_id'] ?>" class="btn-select" style="padding: 5px 15px; font-size: 0.8rem;">Купить</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">Нет доступных маршрутов</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для информации -->
<div id="infoModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="infoModalTitle">Информация</h2>
            <span class="close-modal" onclick="closeInfoModal()">&times;</span>
        </div>
        <div id="infoModalBody"></div>
    </div>
</div>

<script>
    // Открытие модального окна с полным расписанием
    function openFullScheduleModal() {
        document.getElementById('scheduleModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeScheduleModal() {
        document.getElementById('scheduleModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Модальные окна для правил, возврата и акций
    function openModal(type) {
        const modal = document.getElementById('infoModal');
        const modalTitle = document.getElementById('infoModalTitle');
        const modalBody = document.getElementById('infoModalBody');
        
        let title = '';
        let content = '';
        
        if (type === 'rules') {
            title = 'Правила перевозки';
            content = `
                <h3>Общие положения</h3>
                <p>Настоящие правила регулируют порядок перевозки пассажиров и багажа пригородным железнодорожным транспортом.</p>
                <h3>Приобретение билетов</h3>
                <p>Билеты можно приобрести на сайте, в мобильном приложении или в кассах вокзалов.</p>
                <h3>Провоз багажа</h3>
                <p>Разрешается бесплатный провоз ручной клади весом до 36 кг.</p>
            `;
        } else if (type === 'refund') {
            title = 'Возврат билета';
            content = `
                <h3>Сроки возврата</h3>
                <ul>
                    <li><strong>За 8 часов</strong> — 100% стоимости</li>
                    <li><strong>За 2-8 часов</strong> — 95% стоимости</li>
                    <li><strong>Менее 2 часов</strong> — 50% стоимости</li>
                </ul>
                <h3>Как вернуть билет</h3>
                <p>Зайдите в раздел "Мои билеты" и нажмите "Отменить"</p>
            `;
        } else if (type === 'promotions') {
            title = 'Акции и скидки';
            content = `
                <h3>🎫 Счастливые часы</h3>
                <p>Скидка 20% на рейсы до 8:00 и после 20:00</p>
                <h3>👨‍👩‍👧‍👦 Семейный тариф</h3>
                <p>При покупке 3+ билетов — скидка 10%</p>
                <h3>🎓 Студенческая скидка</h3>
                <p>Скидка 25%</p>
            `;
        }
        
        modalTitle.innerHTML = title;
        modalBody.innerHTML = content;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeInfoModal() {
        document.getElementById('infoModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Закрытие по клику на фон
    document.getElementById('scheduleModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeScheduleModal();
    });
    document.getElementById('infoModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeInfoModal();
    });
    
    // Валидация формы
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const from = document.getElementById('fromStation')?.value.trim();
        const to = document.getElementById('toStation')?.value.trim();
        if (!from || !to) {
            e.preventDefault();
            alert('Пожалуйста, укажите города отправления и назначения');
            return false;
        }
        if (from === to) {
            e.preventDefault();
            alert('Города отправления и назначения не могут совпадать');
            return false;
        }
    });
</script>
</body>
</html>