<?php
require_once 'config.php';

// Получаем все станции для фильтра
$stmtStations = $pdo->query("SELECT * FROM stations ORDER BY order_num");
$stations = $stmtStations->fetchAll();

// Получаем все поезда с расписанием
$trains = getAllTrainsSchedule($pdo);

// Детальное расписание для выбранного поезда (AJAX будет использовать)
if (isset($_GET['ajax']) && isset($_GET['train_id'])) {
    header('Content-Type: application/json');
    $schedule = getTrainSchedule($pdo, $_GET['train_id']);
    echo json_encode($schedule);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание поездов Оренбург - Уфа | Вокзал Стерлитамак</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-top">
            <div class="logo-area">
                <i class="fas fa-train logo-icon"></i>
                <div class="logo-text">
                    <h1>Вокзал Пассажир Стерлитамака</h1>
                    <p>Официальный информационный портал</p>
                </div>
            </div>
            <div class="header-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="user-link"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="login-link"><i class="fas fa-user-circle"></i> Вход</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="main-nav">
            <ul class="nav-list">
                <li><a href="index.php">Главная</a></li>
                <li><a href="schedule.php" class="active">Расписание</a></li>
                <li><a href="services.php">Услуги и сервисы</a></li>
                <li><a href="passengers.php">Пассажирам</a></li>
                <li><a href="contacts.php">Контакты</a></li>
            </ul>
        </div>
    </div>
</header>

<main>
    <div class="container">
        <div class="route-info">
            <h2><i class="fas fa-route"></i> Маршрут: Оренбург → Уфа</h2>
            <div class="route-stations">
                <?php foreach($stations as $index => $station): ?>
                    <div class="route-station">
                        <span class="station-name"><?php echo htmlspecialchars($station['name']); ?></span>
                        <span class="station-distance"><?php echo $station['distance_km']; ?> км</span>
                    </div>
                    <?php if($index < count($stations) - 1): ?>
                        <i class="fas fa-arrow-right route-arrow"></i>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <h2 class="section-title">Расписание поездов</h2>
        <div class="schedule-board">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>№ поезда</th>
                        <th>Название</th>
                        <th>Отправление из Оренбурга</th>
                        <th>Прибытие в Уфу</th>
                        <th>Время в пути</th>
                        <th>Тип</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($trains as $train): ?>
                    <tr class="train-row" data-train-id="<?php echo $train['train_id']; ?>">
                        <td><strong><?php echo htmlspecialchars($train['train_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($train['train_name']); ?></td>
                        <td><?php echo date('H:i', strtotime($train['departure_time'])); ?></td>
                        <td><?php echo date('H:i', strtotime($train['arrival_time'])); ?></td>
                        <td><?php echo substr($train['total_travel_time'], 0, 5); ?></td>
                        <td>
                            <?php
                            $typeText = $train['type'] == 'fast' ? 'Скорый' : ($train['type'] == 'suburban' ? 'Пригородный' : 'Пассажирский');
                            echo $typeText;
                            ?>
                        </td>
                        <td>
                            <button class="btn-outline view-schedule" data-train-id="<?php echo $train['train_id']; ?>" data-train-num="<?php echo htmlspecialchars($train['train_number']); ?>">
                                <i class="fas fa-calendar-alt"></i> Подробно
                            </button>
                            <button class="buy-ticket" data-train-id="<?php echo $train['train_id']; ?>" data-train-num="<?php echo htmlspecialchars($train['train_number']); ?>">
                                Купить билет
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Модальное окно с детальным расписанием -->
<div id="detailModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close-modal">&times;</span>
        <h3 id="detailTrainTitle">Расписание поезда</h3>
        <div class="detail-schedule" id="detailSchedule">
            <!-- Сюда загрузится детальное расписание -->
        </div>
    </div>
</div>

<!-- Модальное окно покупки билета -->
<div id="ticketModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3><i class="fas fa-ticket-alt"></i> Оформление билета</h3>
        <form id="ticketForm" class="ticket-form">
            <input type="hidden" id="trainId" name="train_id">
            <input type="text" id="trainNumber" name="train_number" placeholder="Поезд" readonly style="background:#f0f0f0;">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Откуда:</label>
                    <select id="fromStation" name="from_station_id" required>
                        <option value="">Выберите станцию</option>
                        <?php foreach($stations as $station): ?>
                            <option value="<?php echo $station['id']; ?>"><?php echo htmlspecialchars($station['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Куда:</label>
                    <select id="toStation" name="to_station_id" required>
                        <option value="">Выберите станцию</option>
                        <?php foreach($stations as $station): ?>
                            <option value="<?php echo $station['id']; ?>"><?php echo htmlspecialchars($station['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <input type="text" id="passengerName" name="passenger_name" placeholder="ФИО пассажира" required>
            <input type="text" id="passportNum" name="passport_number" placeholder="Серия и номер паспорта" required>
            
            <select id="carriageType" name="carriage_type">
                <option value="Плацкарт">Плацкарт — 2100 ₽</option>
                <option value="Купе">Купе — 3800 ₽</option>
                <option value="Люкс">Люкс — 7200 ₽</option>
            </select>
            
            <button type="submit" class="btn-primary">Оплатить билет</button>
            <div id="modalMessage"></div>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col"><h4>О вокзале</h4><a href="#">История</a><a href="services.php">Схема вокзала</a></div>
            <div class="footer-col"><h4>Пассажирам</h4><a href="passengers.php">Правила багажа</a><a href="passengers.php">Маломобильным</a></div>
            <div class="footer-col"><h4>Быстрые ссылки</h4><a href="schedule.php">Расписание онлайн</a><a href="#" id="footerBuyLink">Покупка билета</a></div>
        </div>
        <div class="copyright">© 2026 Официальный портал железнодорожного вокзала. Все права защищены.</div>
    </div>
</footer>

<script src="script.js"></script>
</body>
</html>