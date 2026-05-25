<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/includes/functions.php';

// Получаем ID маршрута
$scheduleId = $_GET['schedule_id'] ?? 0;
$route = getRouteById($scheduleId);

// Если маршрут не найден, перенаправляем на главную
if (!$route) {
    header('Location: index.php');
    exit;
}

// Обработка отправки формы бронирования
$bookingResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    $passengerName = trim($_POST['passenger_name'] ?? '');
    $passengerPhone = trim($_POST['passenger_phone'] ?? '');
    $passengerEmail = trim($_POST['passenger_email'] ?? '');
    $ticketCount = intval($_POST['ticket_count'] ?? 1);
    
    if ($ticketCount < 1) $ticketCount = 1;
    if ($ticketCount > $route['seats_available']) $ticketCount = $route['seats_available'];
    
    if ($passengerName && $passengerPhone) {
        // Бронируем несколько билетов
        $allSuccess = true;
        $bookedNumbers = [];
        $totalPrice = 0;
        
        for ($i = 1; $i <= $ticketCount; $i++) {
            $result = bookTicket($scheduleId, $passengerName . ($ticketCount > 1 ? " (билет $i)" : ""), $passengerPhone, $_SESSION['user_id'] ?? null);
            if ($result['success']) {
                $bookedNumbers[] = $result['booking_number'];
                $totalPrice += $result['price'];
            } else {
                $allSuccess = false;
                $bookingResult = $result;
                break;
            }
        }
        
        if ($allSuccess && !empty($bookedNumbers)) {
            $bookingResult = [
                'success' => true, 
                'booking_numbers' => $bookedNumbers,
                'ticket_count' => $ticketCount,
                'total_price' => $totalPrice
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление билета - Местный Экспресс</title>
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
            background: linear-gradient(135deg, var(--gray-100) 0%, white 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
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
            cursor: pointer;
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

        /* Основной контент */
        .booking-page {
            padding: 60px 0;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Информация о маршруте */
        .route-card {
            background: white;
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 100px;
        }

        .route-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .route-title i {
            color: var(--primary-red);
        }

        .route-stations {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .station {
            text-align: center;
            flex: 1;
        }

        .station .city {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .station .time {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-red);
            margin: 8px 0;
        }

        .station .date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .route-arrow {
            font-size: 1.5rem;
            color: var(--primary-red);
            padding: 0 20px;
        }

        .route-details {
            padding: 24px 0;
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--gray-200);
        }

        .detail-item {
            flex: 1;
        }

        .detail-label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 600;
            font-size: 1rem;
        }

        .route-price-info {
            padding: 24px 0;
            text-align: center;
        }

        .price-label {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .price-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-red);
        }

        /* Форма бронирования */
        .booking-form-card {
            background: white;
            border-radius: 32px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
        }

        .booking-form-card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .booking-form-card .subtitle {
            color: var(--gray-500);
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: var(--gray-700);
        }

        .form-group label i {
            margin-right: 8px;
            color: var(--primary-red);
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border-radius: 16px;
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

        /* Счетчик билетов */
        .ticket-counter {
            background: var(--gray-50);
            border-radius: 20px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1.5px solid var(--gray-200);
            transition: all 0.2s;
        }

        .ticket-counter:focus-within {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }

        .counter-btn {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: white;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--primary-red);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .counter-btn:hover {
            background: var(--primary-red);
            color: white;
        }

        .counter-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .counter-input {
            width: 80px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: none;
            background: transparent;
            font-family: 'Inter', sans-serif;
        }

        .counter-input:focus {
            outline: none;
        }

        /* Сводка по цене */
        .price-summary {
            background: var(--primary-red-light);
            border-radius: 20px;
            padding: 20px;
            margin: 24px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .price-row.total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px dashed var(--primary-red);
            font-weight: 800;
            font-size: 1.2rem;
        }

        .price-row.total .price {
            color: var(--primary-red);
            font-size: 1.3rem;
        }

        .btn-book {
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
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-book:hover {
            background: var(--primary-red-dark);
            transform: scale(0.98);
        }

        .seats-warning {
            background: #FEF3C7;
            border-radius: 16px;
            padding: 12px 16px;
            margin-top: 16px;
            font-size: 0.8rem;
            color: #92400E;
            display: flex;
            align-items: center;
            gap: 8px;
        }

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

        .modal-content {
            background: white;
            border-radius: 32px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            animation: slideUp 0.3s ease;
        }

        .modal-content i {
            font-size: 4rem;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .modal-content h3 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .booking-numbers {
            background: var(--gray-100);
            padding: 16px;
            border-radius: 16px;
            margin: 20px 0;
            text-align: left;
        }

        .booking-numbers p {
            margin: 5px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }

        .modal-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 24px;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--primary-red);
            color: white;
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            .booking-grid {
                grid-template-columns: 1fr;
            }
            .route-card {
                position: static;
            }
            .route-stations {
                flex-direction: column;
                gap: 16px;
            }
            .route-arrow {
                transform: rotate(90deg);
            }
            .nav-container {
                flex-direction: column;
            }
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

<!-- ОСНОВНОЙ КОНТЕНТ -->
<section class="booking-page">
    <div class="container">
        <div class="booking-grid">
            <!-- Левая колонка: Информация о маршруте -->
            <div class="route-card">
                <div class="route-title">
                    <i class="fas fa-train"></i>
                    <span>Информация о рейсе</span>
                </div>

                <div class="route-stations">
                    <div class="station">
                        <div class="city"><?= htmlspecialchars($route['from_city']) ?></div>
                        <div class="time"><?= date('H:i', strtotime($route['departure_time'])) ?></div>
                        <div class="date"><?= date('d.m.Y', strtotime($route['travel_date'])) ?></div>
                    </div>
                    <div class="route-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="station">
                        <div class="city"><?= htmlspecialchars($route['to_city']) ?></div>
                        <div class="time"><?= date('H:i', strtotime($route['arrival_time'])) ?></div>
                        <div class="date"><?= date('d.m.Y', strtotime($route['travel_date'])) ?></div>
                    </div>
                </div>

                <div class="route-details">
                    <div class="detail-item">
                        <div class="detail-label">Поезд</div>
                        <div class="detail-value">№ <?= htmlspecialchars($route['train_number']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Тип поезда</div>
                        <div class="detail-value">
                            <?php if($route['train_type'] == 'express'): ?>🚄 Экспресс
                            <?php elseif($route['train_type'] == 'fast'): ?>⚡ Скорый
                            <?php else: ?>🚈 Электричка <?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Время в пути</div>
                        <div class="detail-value"><?= floor($route['travel_duration']/60) ?> ч <?= $route['travel_duration']%60 ?> мин</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Свободных мест</div>
                        <div class="detail-value" id="availableSeats">💺 <?= $route['seats_available'] ?></div>
                    </div>
                </div>

                <div class="route-price-info">
                    <div class="price-label">Стоимость одного билета</div>
                    <div class="price-value" id="ticketPrice"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</div>
                </div>
            </div>

            <!-- Правая колонка: Форма бронирования -->
            <div class="booking-form-card">
                <h2><i class="fas fa-user-check"></i> Оформление билетов</h2>
                <div class="subtitle">Заполните данные пассажира</div>

                <form method="POST" action="" id="bookingForm">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> ФИО пассажира *</label>
                        <input type="text" name="passenger_name" id="passengerName" placeholder="Иванов Иван Иванович" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Телефон *</label>
                        <input type="tel" name="passenger_phone" id="passengerPhone" placeholder="+7 (900) 123-45-67" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="passenger_email" id="passengerEmail" placeholder="ivan@example.com">
                    </div>

                    <!-- Выбор количества билетов -->
                    <div class="form-group">
                        <label><i class="fas fa-ticket-alt"></i> Количество билетов</label>
                        <div class="ticket-counter">
                            <button type="button" class="counter-btn" id="decrementBtn" onclick="changeTickets(-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="ticket_count" id="ticketCount" class="counter-input" value="1" min="1" max="<?= $route['seats_available'] ?>" readonly>
                            <button type="button" class="counter-btn" id="incrementBtn" onclick="changeTickets(1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Сводка по цене -->
                    <div class="price-summary">
                        <div class="price-row">
                            <span>Билет (1 шт.)</span>
                            <span class="price" id="unitPriceDisplay"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</span>
                        </div>
                        <div class="price-row total">
                            <span>Итого</span>
                            <span class="price" id="totalPriceDisplay"><?= number_format($route['price'], 0, '.', ' ') ?> ₽</span>
                        </div>
                    </div>

                    <!-- Предупреждение о количестве мест -->
                    <?php if ($route['seats_available'] < 5): ?>
                    <div class="seats-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Осталось всего <?= $route['seats_available'] ?> мест! Торопитесь!
                    </div>
                    <?php endif; ?>

                    <button type="submit" name="book_ticket" class="btn-book" id="bookBtn">
                        <i class="fas fa-ticket-alt"></i> Оформить билеты
                    </button>
                </form>

                <p style="font-size: 0.7rem; color: var(--gray-500); text-align: center; margin-top: 20px;">
                    <i class="fas fa-lock"></i> Ваши данные защищены. Билеты будут отправлены на указанный email.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Модальное окно успеха -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <i class="fas fa-check-circle"></i>
        <h3>Билеты успешно оформлены!</h3>
        <p>Номера ваших билетов:</p>
        <div class="booking-numbers" id="bookingNumbers"></div>
        <p>Информация о билетах отправлена на вашу почту.</p>
        <div class="modal-buttons">
            <a href="my-tickets.php" class="btn-primary">Мои билеты</a>
            <a href="index.php" class="btn-secondary">На главную</a>
        </div>
    </div>
</div>

<!-- ФУТЕР -->
<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            © 2026 Местные железнодорожные перевозки. Все права защищены.
        </div>
    </div>
</footer>

<script>
    // Данные для динамического пересчета
    const basePrice = <?= $route['price'] ?>;
    const maxSeats = <?= $route['seats_available'] ?>;
    let currentCount = 1;

    // Функция изменения количества билетов
    function changeTickets(delta) {
        let newCount = currentCount + delta;
        if (newCount < 1) newCount = 1;
        if (newCount > maxSeats) newCount = maxSeats;
        
        if (newCount !== currentCount) {
            currentCount = newCount;
            document.getElementById('ticketCount').value = currentCount;
            updatePrices();
        }
    }

    // Обновление цен
    function updatePrices() {
        const total = basePrice * currentCount;
        document.getElementById('unitPriceDisplay').innerHTML = formatPrice(basePrice) + ' ₽';
        document.getElementById('totalPriceDisplay').innerHTML = formatPrice(total) + ' ₽';
        
        // Обновляем текст кнопки
        const bookBtn = document.getElementById('bookBtn');
        if (currentCount === 1) {
            bookBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Оформить билет за ' + formatPrice(total) + ' ₽';
        } else {
            bookBtn.innerHTML = '<i class="fas fa-ticket-alt"></i> Оформить ' + currentCount + ' билета за ' + formatPrice(total) + ' ₽';
        }
    }

    // Форматирование цены
    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU').format(price);
    }

    // Обработчик для счетчика (стрелки + мышка)
    document.getElementById('ticketCount')?.addEventListener('change', function(e) {
        let val = parseInt(e.target.value);
        if (isNaN(val)) val = 1;
        if (val < 1) val = 1;
        if (val > maxSeats) val = maxSeats;
        currentCount = val;
        e.target.value = currentCount;
        updatePrices();
    });

    // Инициализация цен
    updatePrices();

    // Валидация формы
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        const name = document.getElementById('passengerName').value.trim();
        const phone = document.getElementById('passengerPhone').value.trim();
        
        if (!name || name.length < 3) {
            e.preventDefault();
            alert('Пожалуйста, введите корректное ФИО (не менее 3 символов)');
            return false;
        }
        
        if (!phone || phone.length < 10) {
            e.preventDefault();
            alert('Пожалуйста, введите корректный номер телефона');
            return false;
        }
    });

    <?php if ($bookingResult && $bookingResult['success']): ?>
        // Показываем модальное окно при успешном бронировании
        let numbersHtml = '';
        <?php foreach($bookingResult['booking_numbers'] as $num): ?>
        numbersHtml += '<p><i class="fas fa-barcode"></i> № <?= $num ?></p>';
        <?php endforeach; ?>
        document.getElementById('bookingNumbers').innerHTML = numbersHtml;
        document.getElementById('successModal').style.display = 'flex';
        
        // Очищаем форму
        document.getElementById('bookingForm').reset();
        document.getElementById('ticketCount').value = 1;
        currentCount = 1;
        updatePrices();
        
        // Закрытие модального окна при клике вне его
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    <?php endif; ?>

    <?php if ($bookingResult && !$bookingResult['success']): ?>
        alert('<?= addslashes($bookingResult['error']) ?>');
    <?php endif; ?>
</script>

</body>
</html>