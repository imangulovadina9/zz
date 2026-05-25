<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/includes/functions.php';

$tickets = [];
$searchPerformed = false;
$searchPhone = '';

// Обработка поиска билетов по телефону
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_tickets'])) {
        $searchPhone = trim($_POST['search_phone'] ?? '');
        $searchPerformed = true;
        
        if ($searchPhone) {
            $db = getDB();
            $sql = "SELECT t.*, 
                    s.travel_date,
                    r.departure_time,
                    r.arrival_time,
                    r.travel_duration,
                    from_st.city as from_city,
                    to_st.city as to_city,
                    tn.train_number,
                    tn.train_type
                    FROM tickets t
                    JOIN schedule s ON t.schedule_id = s.id
                    JOIN routes r ON s.route_id = r.id
                    JOIN stations from_st ON r.from_station_id = from_st.id
                    JOIN stations to_st ON r.to_station_id = to_st.id
                    JOIN trains tn ON r.train_id = tn.id
                    WHERE t.passenger_phone LIKE :phone OR t.passenger_phone = :phone2
                    ORDER BY t.booking_date DESC";
            
            $stmt = $db->prepare($sql);
            $phoneParam = '%' . $searchPhone . '%';
            $stmt->execute([
                ':phone' => $phoneParam,
                ':phone2' => $searchPhone
            ]);
            $tickets = $stmt->fetchAll();
        }
    } elseif (isset($_POST['cancel_ticket']) && isset($_POST['booking_number'])) {
        $bookingNumber = $_POST['booking_number'];
        $db = getDB();
        
        $updateSql = "UPDATE tickets SET status = 'cancelled' WHERE booking_number = :booking";
        $stmt = $db->prepare($updateSql);
        $stmt->execute([':booking' => $bookingNumber]);
        
        echo "<script>alert('Билет #$bookingNumber отменен'); window.location.href='my-tickets.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои билеты - Местный Экспресс</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F1F5F9;
            color: #0F172A;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Навигация */
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            flex-wrap: wrap;
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
            color: #D32F2F;
        }

        .logo span {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #D32F2F);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 28px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #D32F2F;
        }

        .btn-outline-nav {
            border: 1.5px solid #E2E8F0;
            padding: 8px 18px;
            border-radius: 9999px;
        }

        /* Поисковая форма */
        .search-section {
            background: white;
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .search-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-title i {
            color: #D32F2F;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .form-group {
            flex: 2;
            min-width: 250px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.85rem;
            color: #475569;
        }

        .form-group label i {
            margin-right: 6px;
            color: #D32F2F;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border-radius: 16px;
            border: 1.5px solid #E2E8F0;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #D32F2F;
            box-shadow: 0 0 0 3px rgba(211,47,47,0.1);
        }

        .btn-search {
            background: #D32F2F;
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-search:hover {
            background: #B71C1C;
            transform: scale(0.98);
        }

        /* Основной контент */
        .tickets-page {
            padding: 60px 0;
            flex: 1;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: #D32F2F;
        }

        .results-count {
            margin-bottom: 24px;
            padding: 12px 16px;
            background: #EFF6FF;
            border-radius: 12px;
            color: #1E3A5F;
        }

        /* Карточка билета */
        .ticket-card {
            background: white;
            border-radius: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .ticket-header {
            background: linear-gradient(135deg, #D32F2F, #B71C1C);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ticket-number {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .ticket-number i {
            margin-right: 8px;
        }

        .ticket-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-paid {
            background: #4CAF50;
            color: white;
        }

        .status-booked {
            background: #FF9800;
            color: white;
        }

        .status-cancelled {
            background: #9E9E9E;
            color: white;
        }

        .ticket-body {
            padding: 24px;
        }

        .route-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 24px;
        }

        .station {
            text-align: center;
            flex: 1;
        }

        .station .city {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1E293B;
        }

        .station .time {
            font-size: 1rem;
            color: #64748B;
            margin-top: 5px;
        }

        .station .date {
            font-size: 0.8rem;
            color: #94A3B8;
        }

        .route-arrow {
            font-size: 1.5rem;
            color: #D32F2F;
        }

        .ticket-details {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid #E2E8F0;
        }

        .detail-item {
            flex: 1;
        }

        .detail-label {
            font-size: 0.7rem;
            color: #64748B;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 600;
            color: #1E293B;
        }

        .ticket-footer {
            background: #F8FAFC;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            border-top: 1px solid #E2E8F0;
        }

        .ticket-price {
            font-size: 1.3rem;
            font-weight: 800;
            color: #D32F2F;
        }

        .btn-cancel {
            background: none;
            border: 1px solid #D32F2F;
            color: #D32F2F;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #D32F2F;
            color: white;
        }

        .btn-print {
            background: none;
            border: 1px solid #64748B;
            color: #64748B;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background: #64748B;
            color: white;
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
            font-size: 1.3rem;
            color: #475569;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #64748B;
            margin-bottom: 24px;
        }

        .btn-primary {
            background: #D32F2F;
            color: white;
            padding: 12px 32px;
            border-radius: 40px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #B71C1C;
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
            .route-info {
                flex-direction: column;
            }
            .route-arrow {
                transform: rotate(90deg);
            }
            .nav-container {
                flex-direction: column;
            }
            .ticket-header {
                flex-direction: column;
                text-align: center;
            }
            .search-form {
                flex-direction: column;
            }
            .btn-search {
                width: 100%;
                justify-content: center;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ticket-card {
            animation: fadeIn 0.3s ease;
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
            <a href="index.php#schedule">Расписание</a>
            <a href="search-results.php" class="btn-outline-nav">Найти билет</a>
        </div>
    </div>
</nav>

<!-- Основной контент -->
<section class="tickets-page">
    <div class="container">
        <div class="page-title">
            <i class="fas fa-ticket-alt"></i>
            <h1>Мои билеты</h1>
        </div>

        <!-- Форма поиска билетов -->
        <div class="search-section">
            <div class="search-title">
                <i class="fas fa-search"></i>
                <span>Поиск билетов</span>
            </div>
            <form method="POST" action="" class="search-form">
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Номер телефона</label>
                    <input type="tel" name="search_phone" placeholder="+7 (900) 123-45-67" value="<?= htmlspecialchars($searchPhone) ?>" required>
                </div>
                <button type="submit" name="search_tickets" class="btn-search">
                    Найти билеты
                </button>
            </form>
            <p style="font-size: 0.75rem; color: #94A3B8; margin-top: 16px;">
                <i class="fas fa-info-circle"></i> Введите номер телефона, указанный при покупке билета
            </p>
        </div>

        <!-- Результаты поиска -->
        <?php if ($searchPerformed): ?>
            <?php if (!empty($tickets)): ?>
                <div class="results-count">
                    <i class="fas fa-ticket-alt"></i> Найдено билетов: <strong><?= count($tickets) ?></strong>
                </div>
                
                <?php foreach($tickets as $ticket): ?>
                <div class="ticket-card">
                    <div class="ticket-header">
                        <div class="ticket-number">
                            <i class="fas fa-barcode"></i> 
                            № <?= htmlspecialchars($ticket['booking_number']) ?>
                        </div>
                        <div class="ticket-status status-<?= $ticket['status'] ?>">
                            <?php if($ticket['status'] == 'paid'): ?>
                                 Оплачен
                            <?php elseif($ticket['status'] == 'booked'): ?>
                                 Забронирован
                            <?php else: ?>
                                 Отменен
                            <?php endif; ?>
                        </div>
                        <div>
                            <i class="fas fa-calendar"></i> 
                            <?= date('d.m.Y', strtotime($ticket['booking_date'])) ?>
                        </div>
                    </div>

                    <div class="ticket-body">
                        <div class="route-info">
                            <div class="station">
                                <div class="city"><?= htmlspecialchars($ticket['from_city']) ?></div>
                                <div class="time"><?= date('H:i', strtotime($ticket['departure_time'])) ?></div>
                                <div class="date"><?= date('d.m.Y', strtotime($ticket['travel_date'])) ?></div>
                            </div>
                            <div class="route-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="station">
                                <div class="city"><?= htmlspecialchars($ticket['to_city']) ?></div>
                                <div class="time"><?= date('H:i', strtotime($ticket['arrival_time'])) ?></div>
                                <div class="date"><?= date('d.m.Y', strtotime($ticket['travel_date'])) ?></div>
                            </div>
                        </div>

                        <div class="ticket-details">
                            <div class="detail-item">
                                <div class="detail-label">Поезд</div>
                                <div class="detail-value">№ <?= htmlspecialchars($ticket['train_number']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Тип поезда</div>
                                <div class="detail-value">
                                    <?php if($ticket['train_type'] == 'express'): ?>🚄 Экспресс
                                    <?php elseif($ticket['train_type'] == 'fast'): ?>⚡ Скорый
                                    <?php else: ?> Электричка <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Пассажир</div>
                                <div class="detail-value"><?= htmlspecialchars($ticket['passenger_name']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Телефон</div>
                                <div class="detail-value"><?= htmlspecialchars($ticket['passenger_phone']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-footer">
                        <div class="ticket-price">
                            <?= number_format($ticket['ticket_price'], 0, '.', ' ') ?> ₽
                        </div>
                        <div>
                            <?php if($ticket['status'] != 'cancelled'): ?>
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Вы уверены, что хотите отменить билет?');">
                                <input type="hidden" name="booking_number" value="<?= htmlspecialchars($ticket['booking_number']) ?>">
                                <button type="submit" name="cancel_ticket" class="btn-cancel">
                                    <i class="fas fa-times"></i> Отменить
                                </button>
                            </form>
                            <?php endif; ?>
                            <button onclick="printTicket(this)" class="btn-print" data-ticket='<?= htmlspecialchars(json_encode($ticket), ENT_QUOTES) ?>'>
                                <i class="fas fa-print"></i> Распечатать
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Билеты не найдены</h3>
                    <p>По номеру телефона <strong><?= htmlspecialchars($searchPhone) ?></strong> билеты не найдены.<br>Проверьте правильность введенного номера.</p>
                    <a href="index.php" class="btn-primary">Купить билет</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Подсказка для первого посещения -->
            <div class="empty-state">
                <i class="fas fa-train"></i>
                <h3>Поиск билетов</h3>
                <p>Введите номер телефона, указанный при покупке билета,<br>чтобы просмотреть свои билеты.</p>
                <a href="index.php" class="btn-primary">Купить билет</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Футер -->
<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            © 2026 Местные железнодорожные перевозки. Все права защищены.
        </div>
    </div>
</footer>

<script>
    function printTicket(btn) {
        var ticketData = JSON.parse(btn.getAttribute('data-ticket'));
        
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<!DOCTYPE html><html><head><title>Билет №' + ticketData.booking_number + '</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; padding: 40px; }');
        printWindow.document.write('.ticket { border: 2px solid #D32F2F; border-radius: 20px; padding: 30px; max-width: 800px; margin: 0 auto; }');
        printWindow.document.write('.header { text-align: center; border-bottom: 2px solid #D32F2F; padding-bottom: 20px; margin-bottom: 20px; }');
        printWindow.document.write('.header h1 { color: #D32F2F; margin: 0; }');
        printWindow.document.write('.route { display: flex; justify-content: space-between; text-align: center; margin: 30px 0; }');
        printWindow.document.write('.station h3 { margin: 0; font-size: 20px; }');
        printWindow.document.write('.station p { margin: 5px 0; color: #666; }');
        printWindow.document.write('.details { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 30px 0; }');
        printWindow.document.write('.detail { padding: 10px; background: #f5f5f5; border-radius: 10px; }');
        printWindow.document.write('.footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="ticket">');
        printWindow.document.write('<div class="header">');
        printWindow.document.write('<h1>МЕСТНЫЙ ЭКСПРЕСС</h1>');
        printWindow.document.write('<p>Электронный билет</p>');
        printWindow.document.write('</div>');
        printWindow.document.write('<div class="route">');
        printWindow.document.write('<div class="station"><h3>' + ticketData.from_city + '</h3><p>' + ticketData.departure_time + '</p><p>' + ticketData.travel_date + '</p></div>');
        printWindow.document.write('<div style="font-size: 30px;">→</div>');
        printWindow.document.write('<div class="station"><h3>' + ticketData.to_city + '</h3><p>' + ticketData.arrival_time + '</p><p>' + ticketData.travel_date + '</p></div>');
        printWindow.document.write('</div>');
        printWindow.document.write('<div class="details">');
        printWindow.document.write('<div class="detail"><strong>Номер билета</strong><br>' + ticketData.booking_number + '</div>');
        printWindow.document.write('<div class="detail"><strong>Поезд</strong><br>№ ' + ticketData.train_number + '</div>');
        printWindow.document.write('<div class="detail"><strong>Пассажир</strong><br>' + ticketData.passenger_name + '</div>');
        printWindow.document.write('<div class="detail"><strong>Телефон</strong><br>' + ticketData.passenger_phone + '</div>');
        printWindow.document.write('<div class="detail"><strong>Стоимость</strong><br>' + new Intl.NumberFormat('ru-RU').format(ticketData.ticket_price) + ' ₽</div>');
        printWindow.document.write('<div class="detail"><strong>Статус</strong><br>' + (ticketData.status == 'paid' ? 'Оплачен' : 'Забронирован') + '</div>');
        printWindow.document.write('</div>');
        printWindow.document.write('<div class="footer">');
        printWindow.document.write('<p>Документ является электронным билетом<br>При посадке предъявите этот документ</p>');
        printWindow.document.write('</div>');
        printWindow.document.write('</div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
</script>
</body>
</html>