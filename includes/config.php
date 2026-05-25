<?php
// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'rzd_local');
define('DB_USER', 'root');
define('DB_PASS', ''); // Для OpenServer пароль обычно пустой

// Настройки сайта
define('SITE_NAME', 'Местный Экспресс');
define('SITE_URL', 'http://rzd-local/');

// Временная зона
date_default_timezone_set('Europe/Moscow');

// Включение отображения ошибок (только для разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// НЕ устанавливаем сессионные настройки здесь, 
// они будут установлены ДО session_start() в нужном месте
?>