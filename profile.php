<?php

<?php foreach($tickets as $ticket): ?>

<div class="ticket">

<div class="ticket-header">
    <div class="train-number">
        🚆 №<?php echo htmlspecialchars($ticket['train_number']); ?>
    </div>

    <div>
        <?php echo date('d.m.Y H:i', strtotime($ticket['purchase_date'])); ?>
    </div>
</div>

<div class="route">
    <div class="route-point">
        <strong><?php echo htmlspecialchars($ticket['from_station']); ?></strong>
        <span>Отправление</span>
    </div>

    <i class="fas fa-arrow-right"></i>

    <div class="route-point">
        <strong><?php echo htmlspecialchars($ticket['to_station']); ?></strong>
        <span>Прибытие</span>
    </div>
</div>

<div class="info-list">

<div class="info-item">
    👤 <?php echo htmlspecialchars($ticket['passenger_name']); ?>
</div>

<div class="info-item">
    🪪 <?php echo htmlspecialchars($ticket['passport_number']); ?>
</div>

<div class="info-item">
    🛏 <?php echo htmlspecialchars($ticket['carriage_type']); ?>
</div>

<div class="info-item">
    💰 <?php echo number_format($ticket['price'],0,',',' '); ?> ₽
</div>

</div>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="empty">
    <h2>У вас пока нет билетов</h2>

    <a href="schedule.php" class="buy-btn">
        Купить билет
    </a>
</div>

<?php endif; ?>

<div class="account-box">
    <h2>Аккаунт</h2>

    <p><strong>Имя:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>

    <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>

    <a href="logout.php" class="logout-btn">
        Выйти
    </a>
</div>

</div>

</body>
</html>