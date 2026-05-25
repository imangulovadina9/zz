<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/includes/functions.php';

// Получаем параметры поиска из URL
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// Если есть кнопка обмена местами
if (isset($_GET['swap']) && $_GET['swap'] == 1) {
    $temp = $from;
    $from = $to;
    $to = $temp;
}

// Выполняем поиск
$searchResults = [];
$transferResults = [];

if ($from && $to) {
    $searchResults = searchRoutes($from, $to, $date);
    
    if (empty($searchResults)) {
        $transferResults = searchRoutesWithTransfer($from, $to, $date);
    }
}

// Получаем список станций для формы
$stations = getStations();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты поиска - Местный Экспресс</title>
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

        /* Hero блок с формой */
        .search-hero {
            background: linear-gradient(112deg, var(--gray-100) 0%, white 100%);
            padding: 40px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .search-form-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .search-form {
            background: white;
            border-radius: 32px;
            padding: 24px 32px;
            box-shadow: var(--shadow-lg);
        }

        .city-swap {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .city-input-group {
            flex: 1;
            position: relative;
        }

        .city-input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .city-input-group label i {
            margin-right: 6px;
            color: var(--primary-red);
        }

        .city-input-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 24px;
            border: 1.5px solid var(--gray-200);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .city-input-group input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(211,47,47,0.1);
        }

        .swap-button {
            margin-top: 28px;
            cursor: pointer;
            background: var(--gray-100);
            border: 1.5px solid var(--gray-200);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .swap-button:hover {
            background: var(--primary-red);
            border-color: var(--primary-red);
            color: white;
            transform: rotate(180deg);
        }

        .date-group {
            margin-top: 16px;
            margin-bottom: 20px;
        }

        .date-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .date-group input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 24px;
            border: 1.5px solid var(--gray-200);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
        }

        .btn-search {
            background: var(--primary-red);
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 28px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-search:hover {
            background: var(--primary-red-dark);
            transform: scale(0.98);
        }

        /* Результаты поиска */
        .results-page {
            padding: 60px 0;
            background: white;
        }

        .search-info {
            background: var(--primary-red-light);
            padding: 20px 24px;
            border-radius: 20px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-params {
            font-size: 1.1rem;
        }

        .search-params strong {
            color: var(--primary-red);
            font-size: 1.2rem;
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
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.25s;
        }

        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-red-light);
        }

        .result-time {
            display: flex;
            align-items: center;
            gap: 32px;
            flex: 2;
        }

        .departure, .arrival {
            text-align: center;
        }

        .departure strong, .arrival strong {
            font-size: 1.4rem;
            display: block;
            color: var(--gray-800);
        }

        .departure small, .arrival small {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .arrow {
            color: var(--primary-red);
            font-size: 1.2rem;
        }

        .result-info {
            flex: 1;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .train-type {
            padding: 5px 14px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .train-type.express {
            background: #FFE5E5;
            color: var(--primary-red);
        }

        .train-type.electric {
            background: #E5F0FF;
            color: var(--primary-blue);
        }

        .train-type.fast {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .duration, .seats-left, .train-number {
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .result-price {
            text-align: right;
            min-width: 160px;
        }

        .price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        .btn-select {
            background: var(--primary-red);
            color: white;
            padding: 10px 32px;
            border-radius: 9999px;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-select:hover {
            background: var(--primary-red-dark);
            transform: scale(1.02);
        }

        /* Маршруты с пересадкой */
        .transfer-card {
            flex-direction: column;
            align-items: stretch;
        }

        .transfer-badge {
            background: #FFF3E0;
            padding: 10px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .wait-time {
            background: #FF9800;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .transfer-segments {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .segment {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 16px;
        }

        .segment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .segment-time {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 12px;
        }

        .segment-footer {
            display: flex;
            justify-content: space-between;
            padding-top: 12px;
            border-top: 1px solid var(--gray-200);
        }

        .segment-price {
            font-weight: 700;
            color: var(--primary-red);
        }

        .transfer-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .total-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        /* Пустое состояние */
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

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        /* Футер */
        .footer {
            background: #0A0E17;
            color: #94A3B8;
            padding: 40px 0;
            margin-top: 60px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #1E293B;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .container { padding: 0 16px; }
            .nav-container { flex-direction: column; }
            .city-swap { flex-direction: column; }
            .swap-button { margin-top: 0; transform: rotate(90deg); }
            .swap-button:hover { transform: rotate(90deg); }
            .result-time { flex-direction: column; gap: 8px; }
            .arrow { transform: rotate(90deg); }
            .result-price { text-align: center; }
        }
    </style>
</head>
<body>

<!-- НАВИГАЦИЯ -->
<nav class="navbar">
    <div class="container nav-container">
        <a href="index.php" class="logo">
            <i class="fas fa-train"></i>
            <span>МЕСТНЫЙЭКСПРЕСС</span>
        </a>
        <div class="nav-links">
            <a href="index.php#advantages">Преимущества</a>
            <a href="index.php#schedule">Расписание</a>
            <a href="my-tickets.php" class="btn-outline-nav"><i class="fas fa-ticket-alt"></i> Мои билеты</a>
        </div>
    </div>
</nav>

<!-- ФОРМА ПОИСКА -->
<section class="search-hero">
    <div class="container">
        <div class="search-form-wrapper">
            <form method="GET" action="search-results.php" class="search-form">
                <div class="city-swap">
                    <div class="city-input-group">
                        <label><i class="fas fa-map-marker-alt"></i> Откуда</label>
                        <input type="text" name="from" id="fromCity" value="<?= htmlspecialchars($from) ?>" placeholder="Например: Уфа" required>
                    </div>
                    <div class="swap-button" onclick="swapCities()">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="city-input-group">
                        <label><i class="fas fa-flag-checkered"></i> Куда</label>
                        <input type="text" name="to" id="toCity" value="<?= htmlspecialchars($to) ?>" placeholder="Например: Баймак" required>
                    </div>
                </div>
                <div class="date-group">
                    <label><i class="fas fa-calendar-alt"></i> Дата поездки</label>
                    <input type="date" name="date" value="<?= $date ?>" min="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Найти билеты</button>
            </form>
        </div>
    </div>
</section>

<!-- РЕЗУЛЬТАТЫ ПОИСКА -->
<section class="results-page">
    <div class="container">
        
        <!-- Информация о поиске -->
        <div class="search-info">
            <div class="search-params">
                📍 <strong><?= htmlspecialchars($from) ?></strong> → <strong><?= htmlspecialchars($to) ?></strong>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                📅 <?= date('d.m.Y', strtotime($date)) ?>
            </div>
        </div>

        <!-- ПРЯМЫЕ РЕЙСЫ -->
        <?php if (!empty($searchResults)): ?>
            <h2 class="section-title">🚆 Прямые <span>рейсы</span></h2>
            <div class="results-list">
                <?php foreach($searchResults as $route): ?>
                <div class="result-card">
                    <div class="result-time">
                        <div class="departure">
                            <strong><?= date('H:i', strtotime($route['departure_time'])) ?></strong>
                            <small><?= htmlspecialchars($route['from_city']) ?></small>
                        </div>
                        <div class="arrow"><i class="fas fa-arrow-right"></i></div>
                        <div class="arrival">
                            <strong><?= date('H:i', strtotime($route['arrival_time'])) ?></strong>
                            <small><?= htmlspecialchars($route['to_city']) ?></small>
                        </div>
                    </div>
                    <div class="result-info">
                        <span class="train-type <?= $route['train_type'] ?>">
                            <?php if($route['train_type'] == 'express'): ?>🚄 Экспресс
                            <?php elseif($route['train_type'] == 'fast'): ?>⚡ Скорый
                            <?php else: ?>🚈 Электричка <?php endif; ?>
                        </span>
                        <span class="duration">⏱ <?= floor($route['travel_duration']/60) ?>ч <?= $route['travel_duration']%60 ?>мин</span>
                        <span class="seats-left">💺 Свободно: <?= $route['seats_available'] ?></span>
                        <span class="train-number">🚆 №<?= htmlspecialchars($route['train_number']) ?></span>
                    </div>
                    <div class="result-price">
                        <div class="price"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</div>
                        <a href="booking.php?schedule_id=<?= $route['schedule_id'] ?>" class="btn-select">Выбрать</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- МАРШРУТЫ С ПЕРЕСАДКОЙ -->
        <?php if (!empty($transferResults)): ?>
            <h2 class="section-title" style="margin-top: 60px;">🔄 Маршруты <span>с пересадкой</span></h2>
            <div class="results-list">
                <?php foreach($transferResults as $transfer): ?>
                <div class="result-card transfer-card">
                    <div class="transfer-badge">
                        <span><i class="fas fa-exchange-alt"></i> Пересадка в <strong><?= htmlspecialchars($transfer['transfer_city']) ?></strong></span>
                        <span class="wait-time">⏱ Ожидание: <?= floor($transfer['wait_time']) ?> мин</span>
                    </div>
                    <div class="transfer-segments">
                        <!-- Сегмент 1 -->
                        <div class="segment">
                            <div class="segment-header">
                                <span>🚆 <?= htmlspecialchars($transfer['first_segment']['train_number']) ?></span>
                                <span class="train-type <?= $transfer['first_segment']['train_type'] ?>">
                                    <?= $transfer['first_segment']['train_type'] == 'express' ? 'Экспресс' : 'Пригородный' ?>
                                </span>
                            </div>
                            <div class="segment-time">
                                <div>
                                    <strong><?= date('H:i', strtotime($transfer['first_segment']['departure_time'])) ?></strong>
                                    <div><?= htmlspecialchars($transfer['first_segment']['from_city']) ?></div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                                <div>
                                    <strong><?= date('H:i', strtotime($transfer['first_segment']['arrival_time'])) ?></strong>
                                    <div><?= htmlspecialchars($transfer['first_segment']['to_city']) ?></div>
                                </div>
                                <div class="segment-price"><?= number_format($transfer['first_segment']['price'], 0, '.', ' ') ?> ₽</div>
                            </div>
                        </div>
                        <!-- Сегмент 2 -->
                        <div class="segment">
                            <div class="segment-header">
                                <span>🚆 <?= htmlspecialchars($transfer['second_segment']['train_number']) ?></span>
                                <span class="train-type <?= $transfer['second_segment']['train_type'] ?>">
                                    <?= $transfer['second_segment']['train_type'] == 'express' ? 'Экспресс' : 'Пригородный' ?>
                                </span>
                            </div>
                            <div class="segment-time">
                                <div>
                                    <strong><?= date('H:i', strtotime($transfer['second_segment']['departure_time'])) ?></strong>
                                    <div><?= htmlspecialchars($transfer['second_segment']['from_city']) ?></div>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                                <div>
                                    <strong><?= date('H:i', strtotime($transfer['second_segment']['arrival_time'])) ?></strong>
                                    <div><?= htmlspecialchars($transfer['second_segment']['to_city']) ?></div>
                                </div>
                                <div class="segment-price"><?= number_format($transfer['second_segment']['price'], 0, '.', ' ') ?> ₽</div>
                            </div>
                        </div>
                    </div>
                    <div class="transfer-total">
                        <div>💰 Общая стоимость: <span class="total-price"><?= number_format($transfer['total_price'], 0, '.', ' ') ?> ₽</span></div>
                        <div>⏱ Общее время: <?= floor($transfer['total_duration']/60) ?>ч <?= $transfer['total_duration']%60 ?>мин</div>
                        <button class="btn-select" onclick="alert('Для билетов с пересадкой билеты нужно покупать отдельно на каждый сегмент')">Выбрать</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ЕСЛИ НИЧЕГО НЕ НАЙДЕНО -->
        <?php if (empty($searchResults) && empty($transferResults) && $from && $to): ?>
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Рейсов не найдено</h3>
                <p>К сожалению, по вашему запросу <strong><?= htmlspecialchars($from) ?> → <?= htmlspecialchars($to) ?></strong> на <strong><?= date('d.m.Y', strtotime($date)) ?></strong> нет доступных рейсов.</p>
                <p style="margin-top: 16px;">💡 Попробуйте:</p>
                <ul style="list-style: none; margin-top: 8px;">
                    <li>• Выбрать другую дату</li>
                    <li>• Изменить город отправления или назначения</li>
                </ul>
                <a href="index.php" class="btn-select" style="margin-top: 24px; display: inline-block;">← Вернуться на главную</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ФУТЕР -->
<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            © 2026 Местные железнодорожные перевозки. Все права защищены.
        </div>
    </div>
</footer>

<script>
    // Функция для обмена местами (откуда/куда)
    function swapCities() {
        const fromInput = document.getElementById('fromCity');
        const toInput = document.getElementById('toCity');
        const temp = fromInput.value;
        fromInput.value = toInput.value;
        toInput.value = temp;
        
        // Отправляем форму с параметром swap
        const form = document.querySelector('.search-form');
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('from', fromInput.value);
        currentUrl.searchParams.set('to', toInput.value);
        currentUrl.searchParams.set('swap', '1');
        window.location.href = currentUrl.toString();
    }
</script>

</body>
</html>