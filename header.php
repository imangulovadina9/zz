<header class="header">
    <div class="container">
        <div class="logo">
            <h1>Вокзал Стерлитамак</h1>
            <p>Официальный портал</p>
        </div>
        <nav class="nav">
            <a href="index.php">Главная</a>
            <a href="schedule.php">Расписание</a>
            <a href="services.php">Услуги</a>
            <a href="contacts.php">Контакты</a>
        </nav>
        <div class="user-actions">
            <?php if(isset($_SESSION['user_name'])): ?>
                <span style="color:white;">Привет, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>