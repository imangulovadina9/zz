<?php
require_once 'config.php'; // Эта строка должна быть ПЕРВОЙ

// Проверяем, что $pdo существует
if (!isset($pdo)) {
    die("Ошибка: подключение к базе данных не установлено");
}

// Получаем последние новости (3 штуки)
$stmtNews = $pdo->query("SELECT * FROM news ORDER BY date DESC LIMIT 3");
$newsList = $stmtNews->fetchAll();

// Получаем ближайшие поезда (первые 5)
$stmtTrains = $pdo->query("
    SELECT t.id, t.train_number, t.name, t.type,
           s1.departure_time as start_time,
           s2.arrival_time as end_time,
           TIMEDIFF(s2.arrival_time, s1.departure_time) as travel_time
    FROM trains t
    JOIN schedule s1 ON t.id = s1.train_id AND s1.station_id = 1
    JOIN schedule s2 ON t.id = s2.train_id AND s2.station_id = 7
    WHERE t.is_active = 1
    ORDER BY s1.departure_time
    LIMIT 5
");
$trainsList = $stmtTrains->fetchAll();

// Получаем услуги для отображения (первые 4)
$stmtServices = $pdo->query("SELECT * FROM services LIMIT 4");
$servicesList = $stmtServices->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Вокзал Стерлитамак | Официальный портал</title>
    <meta name="description" content="Официальный сайт железнодорожного вокзала Стерлитамак. Расписание поездов Оренбург-Уфа, покупка билетов онлайн, услуги вокзала, новости.">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php 
// Запускаем сессию для работы с пользователем
session_start();
include 'header.php'; 
?>

<main>
    <!-- Hero блок с большой кнопкой покупки билета -->
    <div class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Расписание поездов онлайн</h2>
                
                <!-- Список всех станций маршрута -->
                <div class="hero-stations">
                    <span>Оренбург</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Кумертау</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Мелеуз</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Салават</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Стерлитамак</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Кармаскалы</span> 
                    <i class="fas fa-arrow-right"></i>
                    <span>Уфа</span>
                </div>
                
                <!-- Быстрый поиск расписания -->
                <div class="search-form">
                    <input type="text" id="searchFrom" placeholder="Откуда" list="stations-list">
                    <input type="text" id="searchTo" placeholder="Куда" list="stations-list">
                    <input type="date" id="searchDate">
                    <button class="btn-primary" onclick="window.location.href='schedule.php'">Найти</button>
                </div>

                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Ближайшие поезда -->
        <section>
            <div class="section-header">
                <h2 class="section-title">Ближайшие поезда</h2>
                <a href="schedule.php" class="view-all">Смотреть все →</a>
            </div>
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
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($trainsList) > 0): ?>
                            <?php foreach($trainsList as $train): ?>
                            <tr>
                                <td class="train-number"><?php echo htmlspecialchars($train['train_number']); ?></td>
                                <td><?php echo htmlspecialchars($train['name']); ?></td>
                                <td class="time"><?php echo date('H:i', strtotime($train['start_time'])); ?></td>
                                <td class="time"><?php echo date('H:i', strtotime($train['end_time'])); ?></td>
                                <td><?php echo substr($train['travel_time'], 0, 5); ?></td>
                                <td>
                                    <?php 
                                    $typeClass = '';
                                    $typeText = '';
                                    switch($train['type']) {
                                        case 'fast': $typeClass = 'fast'; $typeText = 'Скорый'; break;
                                        case 'suburban': $typeClass = 'suburban'; $typeText = 'Пригородный'; break;
                                        default: $typeClass = 'passenger'; $typeText = 'Пассажирский';
                                    }
                                    ?>
                                    <span class="train-type <?php echo $typeClass; ?>"><?php echo $typeText; ?></span>
                                </td>
                                <td>
                                    <a href="train_detail.php?id=<?php echo $train['id']; ?>" class="btn-outline btn-sm">Подробнее</a>
                                    <button class="buy-ticket btn-sm" data-train-id="<?php echo $train['id']; ?>" data-train-num="<?php echo htmlspecialchars($train['train_number']); ?>">Купить</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-row">Нет доступных поездов. Добавьте данные в базу.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Услуги и сервисы вокзала -->
        <section>
            <div class="section-header">
                <h2 class="section-title">Услуги и сервисы вокзала</h2>
                <a href="services.php" class="view-all">Все услуги →</a>
            </div>
            <div class="services-grid">
                <?php if(count($servicesList) > 0): ?>
                    <?php foreach($servicesList as $service): ?>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="<?php echo $service['icon_class']; ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <button class="btn-outline" onclick="window.location.href='services.php'">Подробнее</button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-train"></i>
                        </div>
                        <h3>Добавьте услуги</h3>
                        <p>Заполните таблицу services в базе данных</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Новости вокзала -->
        <section>
            <div class="section-header">
                <h2 class="section-title">Новости вокзала</h2>
                <a href="#" class="view-all">Все новости →</a>
            </div>
            <div class="news-row">
                <?php if(count($newsList) > 0): ?>
                    <?php foreach($newsList as $news): ?>
                    <div class="news-item">
                        <div class="news-date">
                            <i class="far fa-calendar-alt"></i> 
                            <?php echo date('d F Y', strtotime($news['date'])); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($news['content'], 0, 120)) . '...'; ?></p>
                        <a href="#" class="read-more">Читать далее →</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="news-item">
                        <div class="news-date">Добавьте новости</div>
                        <h3>Новостей пока нет</h3>
                        <p>Заполните таблицу news в базе данных</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Информационные блоки -->
        <section class="info-blocks">
            <div class="info-block">
                <i class="fas fa-clock"></i>
                <h3>Режим работы</h3>
                <p>Ежедневно: 05:00 - 23:00<br>Кассы: 06:00 - 22:00</p>
            </div>
            <div class="info-block">
                <i class="fas fa-phone-alt"></i>
                <h3>Справочная служба</h3>
                <p>8-800-775-52-67<br>Звонок по России бесплатный</p>
            </div>
            <div class="info-block">
                <i class="fas fa-wheelchair"></i>
                <h3>Доступная среда</h3>
                <p>Пандусы, лифты,<br>услуги сопровождения</p>
            </div>
            <div class="info-block">
                <i class="fas fa-wifi"></i>
                <h3>Бесплатный Wi-Fi</h3>
                <p>Доступен во всех<br>зонах ожидания</p>
            </div>
        </section>
    </div>
</main>

<?php include 'footer.php'; ?>

<!-- Модальное окно покупки билета -->
<div id="ticketModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3><i class="fas fa-ticket-alt"></i> Оформление билета</h3>
        <form id="ticketForm" class="ticket-form">
            <input type="hidden" name="train_id" id="modalTrainId">
            <input type="text" id="modalTrainNumber" name="train_number" placeholder="Поезд" readonly style="background:#f0f0f0;">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Откуда:</label>
                    <select name="from_station_id" id="modalFromStation" required>
                        <option value="">Выберите станцию</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Куда:</label>
                    <select name="to_station_id" id="modalToStation" required>
                        <option value="">Выберите станцию</option>
                    </select>
                </div>
            </div>
            
            <input type="text" name="passenger_name" id="passengerName" placeholder="ФИО пассажира" required>
            <input type="text" name="passport_number" id="passportNum" placeholder="Серия и номер паспорта" required>
            
            <select name="carriage_type" id="carriageType">
                <option value="Плацкарт">Плацкарт — 2100 ₽</option>
                <option value="Купе">Купе — 3800 ₽</option>
                <option value="Люкс">Люкс — 7200 ₽</option>
            </select>
            
            <button type="submit" class="btn-primary btn-block">Оплатить билет</button>
            <div id="modalMessage"></div>
        </form>
    </div>
</div>

<!-- Список станций для автодополнения -->
<datalist id="stations-list">
    <option value="Оренбург">
    <option value="Кумертау">
    <option value="Мелеуз">
    <option value="Салават">
    <option value="Стерлитамак">
    <option value="Кармаскалы">
    <option value="Уфа">
</datalist>

<script src="script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    async function loadStations() {
        try {
            const response = await fetch('get_stations.php');
            const stations = await response.json();
            const fromSelect = document.getElementById('modalFromStation');
            const toSelect = document.getElementById('modalToStation');
            
            if (fromSelect && toSelect) {
                fromSelect.innerHTML = '<option value="">Выберите станцию</option>';
                toSelect.innerHTML = '<option value="">Выберите станцию</option>';
                
                stations.forEach(station => {
                    fromSelect.innerHTML += `<option value="${station.id}">${station.name}</option>`;
                    toSelect.innerHTML += `<option value="${station.id}">${station.name}</option>`;
                });
            }
        } catch (error) {
            console.error('Ошибка загрузки станций:', error);
        }
    }
    
    loadStations();
});
</script>
</body>
</html>