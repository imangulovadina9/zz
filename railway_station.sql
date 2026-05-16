-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 16 2026 г., 07:32
-- Версия сервера: 8.0.30
-- Версия PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `railway_station`
--

-- --------------------------------------------------------

--
-- Структура таблицы `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `news`
--

INSERT INTO `news` (`id`, `title`, `content`, `date`, `created_at`) VALUES
(1, 'Новый скоростной поезд', 'С 1 марта запущен новый скоростной поезд \"Урал\", который сократит время в пути до Уфы на 30 минут.', '2026-02-15', '2026-05-16 04:30:17'),
(2, 'Изменение расписания', 'С 20 февраля изменяется расписание пригородных поездов. Просим пассажиров ознакомиться.', '2026-02-10', '2026-05-16 04:30:17'),
(3, 'Акция для студентов', 'Студентам предоставляется скидка 25% на билеты при предъявлении студенческого билета.', '2026-02-05', '2026-05-16 04:30:17');

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int NOT NULL,
  `train_id` int NOT NULL,
  `station_id` int NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `departure_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `schedule`
--

INSERT INTO `schedule` (`id`, `train_id`, `station_id`, `arrival_time`, `departure_time`) VALUES
(1, 1, 1, NULL, '08:00:00'),
(2, 1, 2, '10:30:00', '10:35:00'),
(3, 1, 3, '12:00:00', '12:05:00'),
(4, 1, 4, '13:30:00', '13:35:00'),
(5, 1, 5, '15:00:00', '15:05:00'),
(6, 1, 6, '16:30:00', '16:35:00'),
(7, 1, 7, '18:00:00', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `icon_class` varchar(50) DEFAULT 'fas fa-service'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `services`
--

INSERT INTO `services` (`id`, `title`, `description`, `icon_class`) VALUES
(1, 'Комната матери и ребёнка', 'Уютная комната для пассажиров с детьми до 7 лет', 'fas fa-baby-carriage'),
(2, 'Камера хранения', 'Круглосуточная камера хранения багажа', 'fas fa-luggage-cart'),
(3, 'Медпункт', 'Квалифицированная медицинская помощь', 'fas fa-ambulance'),
(4, 'Зал ожидания повышенной комфортности', 'Комфортабельные кресла, телевизор, чай', 'fas fa-couch');

-- --------------------------------------------------------

--
-- Структура таблицы `stations`
--

CREATE TABLE `stations` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `order_num` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `stations`
--

INSERT INTO `stations` (`id`, `name`, `order_num`) VALUES
(1, 'Оренбург', 1),
(2, 'Кумертау', 2),
(3, 'Мелеуз', 3),
(4, 'Салават', 4),
(5, 'Стерлитамак', 5),
(6, 'Кармаскалы', 6),
(7, 'Уфа', 7);

-- --------------------------------------------------------

--
-- Структура таблицы `tickets`
--

CREATE TABLE `tickets` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `train_id` int NOT NULL,
  `from_station_id` int NOT NULL,
  `to_station_id` int NOT NULL,
  `passenger_name` varchar(200) NOT NULL,
  `passport_number` varchar(50) NOT NULL,
  `carriage_type` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `booking_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `trains`
--

CREATE TABLE `trains` (
  `id` int NOT NULL,
  `train_number` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type` enum('fast','suburban','passenger') DEFAULT 'passenger',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `trains`
--

INSERT INTO `trains` (`id`, `train_number`, `name`, `type`, `is_active`) VALUES
(1, '001А', 'Урал', 'fast', 1),
(2, '002Б', 'Башкортостан', 'passenger', 1),
(3, '003В', 'Южный Урал', 'fast', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Тестовый Пользователь', 'test@test.com', '+7-999-999-99-99', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-05-16 04:30:17');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `train_id` (`train_id`),
  ADD KEY `station_id` (`station_id`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `train_id` (`train_id`),
  ADD KEY `from_station_id` (`from_station_id`),
  ADD KEY `to_station_id` (`to_station_id`);

--
-- Индексы таблицы `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `trains`
--
ALTER TABLE `trains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`train_id`) REFERENCES `trains` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`from_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`to_station_id`) REFERENCES `stations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
