<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$train_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT t.*,
           s1.departure_time as start_time,
           s2.arrival_time as end_time
    FROM trains t
    JOIN schedule s1 ON t.id = s1.train_id AND s1.station_id = 1
    JOIN schedule s2 ON t.id = s2.train_id AND s2.station_id = 7
    WHERE t.id = ?
");
$stmt->execute([$train_id]);
$train = $stmt->fetch();

if (!$train) die("Поезд не найден.");

$stmtSchedule = $pdo->prepare("
    SELECT st.id, st.name, st.distance_km, s.arrival_time, s.departure_time, s.stop_minutes
    FROM schedule s
    JOIN stations st ON s.station_id = st.id
    WHERE s.train_id = ?
    ORDER BY st.order_num
");
$stmtSchedule->execute([$train_id]);
$schedule = $stmtSchedule->fetchAll();

$stmtCheck = $pdo->prepare("
    SELECT * FROM tickets
    WHERE user_id = ? AND train_id = ?
");
$stmtCheck->execute([$_SESSION['user_id'], $train_id]);
$hasTicket = $stmtCheck->rowCount() > 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поезд №<?php echo htmlspecialchars($train['train_number']); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, #1e3a8a 0%, transparent 30%),
                radial-gradient(circle at bottom right, #7c3aed 0%, transparent 30%),
                #0f172a;
            color: white;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px;
        }

</html>