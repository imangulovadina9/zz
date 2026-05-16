<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - Вокзал Стерлитамак</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container" style="padding: 60px 20px;">
        <h1 class="section-title">Контактная информация</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; margin-top: 40px;">
            <div class="service-card">
                <i class="fas fa-phone-alt" style="font-size: 48px; color: #e67e22;"></i>
                <h3>Справочная служба</h3>
                <p>8-800-775-52-67</p>
                <p>Ежедневно: 05:00 - 23:00</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-envelope" style="font-size: 48px; color: #e67e22;"></i>
                <h3>Электронная почта</h3>
                <p>info@str-rzd.ru</p>
                <p>support@str-rzd.ru</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-map-marker-alt" style="font-size: 48px; color: #e67e22;"></i>
                <h3>Адрес</h3>
                <p>Республика Башкортостан</p>
                <p>г. Стерлитамак, ул. Вокзальная, 21Г</p>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>