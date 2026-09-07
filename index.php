<?php
require_once 'config.php';

// Обработка добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Форматируем дату из "2026-09-07" в "07.09 пн"
        $date_input = $_POST['event_date'];
        $timestamp = strtotime($date_input);
        $day = date('d.m', $timestamp);
        $weekday_ru = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'][date('w', $timestamp)];
        $date_label = $day . ' ' . $weekday_ru;
        
        // Форматируем время из "17:00" и "18:00" в "17.00 - 18.00"
        $time_start = str_replace(':', '.', $_POST['time_start']);
        $time_end = str_replace(':', '.', $_POST['time_end']);
        $time_range = $time_start . ' - ' . $time_end;
        
        $stmt = $pdo->prepare("INSERT INTO events (date_label, time_range, location, title, description, coverage) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $date_label,
            $time_range,
            $_POST['location'],
            $_POST['title'],
            $_POST['description'],
            $_POST['coverage']
        ]);
        
        if ($result) {
            // Успешное добавление
        } else {
            // Ошибка добавления
            error_log("Ошибка добавления: " . print_r($stmt->errorInfo(), true));
        }
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    } elseif ($_POST['action'] === 'clear') {
        $pdo->exec("TRUNCATE TABLE events");
    }
    header("Location: index.php");
    exit;
}

// Получение данных
$events = $pdo->query("SELECT * FROM events ORDER BY STR_TO_DATE(SUBSTRING_INDEX(date_label, ' ', 1), '%d.%m') ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEON PLAN | Еженедельный план</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-green: #39FF14;
            --neon-green-bright: #00FF41;
            --purple-dark: #0a0014;
            --purple-mid: #1a0033;
            --purple-light: #6a0dad;
            --purple-glow: #9d4edd;
            --neon-pink: #ff006e;
            --text-light: #e0e0ff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--purple-dark);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(106, 13, 173, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(57, 255, 20, 0.15) 0%, transparent 50%);
            color: var(--text-light);
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(57, 255, 20, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(57, 255, 20, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .container { max-width: 1400px; margin: 0 auto; position: relative; z-index: 1; }

        h1 {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            font-size: 3em;
            font-weight: 900;
            margin-bottom: 10px;
            color: var(--neon-green);
            text-shadow: 0 0 10px var(--neon-green), 0 0 40px var(--neon-green-bright);
            letter-spacing: 3px;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 10px var(--neon-green), 0 0 20px var(--neon-green); }
            to { text-shadow: 0 0 20px var(--neon-green), 0 0 60px var(--neon-green-bright), 0 0 100px var(--neon-green-bright); }
        }

        .subtitle {
            text-align: center;
            color: var(--purple-glow);
            font-size: 1.2em;
            margin-bottom: 40px;
            text-shadow: 0 0 10px var(--purple-glow);
            letter-spacing: 2px;
        }

        /* Уведомления */
        .notification {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
            animation: slideIn 0.3s ease-out;
        }

        .notification.success {
            background: rgba(57, 255, 20, 0.2);
            border: 2px solid var(--neon-green);
            color: var(--neon-green);
            box-shadow: 0 0 20px rgba(57, 255, 20, 0.5);
        }

        .notification.error {
            background: rgba(255, 0, 110, 0.2);
            border: 2px solid var(--neon-pink);
            color: var(--neon-pink);
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.5);
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Форма */
        .form-section {
            background: linear-gradient(135deg, rgba(26, 0, 51, 0.9), rgba(10, 0, 20, 0.9));
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            border: 2px solid var(--purple-light);
            box-shadow: 0 0 30px rgba(106, 13, 173, 0.5);
        }

        .form-section h3 {
            font-family: 'Orbitron', sans-serif;
            color: var(--neon-green);
            margin-bottom: 25px;
            font-size: 1.5em;
            text-shadow: 0 0 10px var(--neon-green);
        }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; }

        label {
            font-weight: 700; margin-bottom: 8px; font-size: 0.95em;
            color: var(--purple-glow); text-transform: uppercase; letter-spacing: 1px;
        }

        .required::after {
            content: ' *';
            color: var(--neon-pink);
        }

        input, textarea {
            padding: 12px;
            background: rgba(10, 0, 20, 0.8);
            border: 2px solid var(--purple-light);
            border-radius: 8px;
            font-size: 15px;
            color: var(--text-light);
            font-family: 'Rajdhani', sans-serif;
            transition: all 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--neon-green);
            box-shadow: 0 0 15px rgba(57, 255, 20, 0.5);
        }

        input:invalid {
            border-color: var(--neon-pink);
        }

        textarea { resize: vertical; min-height: 100px; }

        /* Стили для date и time input */
        input[type="date"], input[type="time"] {
            color-scheme: dark;
        }

        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) hue-rotate(180deg);
            cursor: pointer;
        }

        .time-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .time-range span {
            color: var(--neon-green);
            font-weight: bold;
        }

        .btn-group { margin-top: 25px; display: flex; gap: 15px; flex-wrap: wrap; }

        button {
            padding: 14px 28px; cursor: pointer; border: none; border-radius: 8px;
            font-weight: 700; font-family: 'Orbitron', sans-serif; font-size: 0.95em;
            letter-spacing: 1px; transition: all 0.3s; text-transform: uppercase;
        }

        .btn-add {
            background: linear-gradient(135deg, var(--neon-green), var(--neon-green-bright));
            color: var(--purple-dark);
            box-shadow: 0 0 20px rgba(57, 255, 20, 0.5);
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(57, 255, 20, 0.8); }

        .btn-print {
            background: linear-gradient(135deg, var(--purple-light), var(--purple-glow));
            color: white; box-shadow: 0 0 20px rgba(157, 78, 221, 0.5);
        }
        .btn-print:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(157, 78, 221, 0.8); }

        .btn-clear {
            background: linear-gradient(135deg, #ff006e, #ff4081);
            color: white; box-shadow: 0 0 20px rgba(255, 0, 110, 0.5);
        }
        .btn-clear:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(255, 0, 110, 0.8); }

        /* Таблица */
        .table-container {
            background: linear-gradient(135deg, rgba(26, 0, 51, 0.9), rgba(10, 0, 20, 0.9));
            padding: 25px; border-radius: 15px;
            border: 2px solid var(--purple-light);
            box-shadow: 0 0 30px rgba(106, 13, 173, 0.5);
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px; }

        th {
            background: linear-gradient(135deg, var(--purple-mid), var(--purple-dark));
            color: var(--neon-green); padding: 15px; text-align: center;
            font-family: 'Orbitron', sans-serif; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 3px solid var(--neon-green);
            text-shadow: 0 0 10px var(--neon-green);
        }

        td {
            padding: 15px; border-bottom: 1px solid rgba(106, 13, 173, 0.3);
            vertical-align: top; transition: all 0.3s;
        }

        tr { background: rgba(10, 0, 20, 0.5); transition: all 0.3s; }
        tr:hover { background: rgba(57, 255, 20, 0.05); box-shadow: inset 0 0 20px rgba(57, 255, 20, 0.1); }
        tr:hover td { border-bottom-color: var(--neon-green); }

        .action-cell { text-align: center; width: 60px; }

        .delete-btn {
            background: rgba(255, 0, 110, 0.2); border: 2px solid var(--neon-pink);
            color: var(--neon-pink); padding: 8px 12px; border-radius: 5px;
            cursor: pointer; font-size: 16px; transition: all 0.3s;
        }
        .delete-btn:hover {
            background: var(--neon-pink); color: white;
            box-shadow: 0 0 20px rgba(255, 0, 110, 0.6); transform: scale(1.1);
        }

        @media print {
            body { background: white; color: black; }
            .form-section, .btn-group, .action-cell, .subtitle, .notification { display: none !important; }
            h1 { color: black; text-shadow: none; }
            .table-container { background: white; border: none; box-shadow: none; }
            table { border: 2px solid black; }
            th { background: #333; color: white; border: 1px solid black; text-shadow: none; }
            td { border: 1px solid black; color: black; }
        }

        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            .form-grid { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 10px 8px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>⚡ ПЛАН МЕРОПРИЯТИЙ ⚡</h1>
    <div class="subtitle">НЕДЕЛЬНЫЙ ПЛАН | MYSQL + PHP</div>

    <?php if (isset($_GET['added'])): ?>
        <div class="notification success">✓ Мероприятие успешно добавлено!</div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="notification error">⚠ Ошибка при добавлении мероприятия</div>
    <?php endif; ?>

    <!-- Форма -->
    <div class="form-section">
        <h3>➕ ДОБАВИТЬ МЕРОПРИЯТИЕ</h3>
        <form method="POST" id="addForm">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-group">
                    <label class="required">Дата мероприятия</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label class="required">Время начала</label>
                    <input type="time" name="time_start" required>
                </div>
                <div class="form-group">
                    <label class="required">Время окончания</label>
                    <input type="time" name="time_end" required>
                </div>
                <div class="form-group">
                    <label class="required">Место проведения</label>
                    <input type="text" name="location" placeholder="Например: МЦ Алпамыш" required>
                </div>
                <div class="form-group">
                    <label class="required">Название мероприятия</label>
                    <input type="text" name="title" placeholder="Например: ОФК Волейбол" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Описание мероприятия</label>
                    <textarea name="description" placeholder="Подробное описание..."></textarea>
                </div>
                <div class="form-group">
                    <label>Примерный охват</label>
                    <input type="text" name="coverage" placeholder="Например: 30 человек">
                </div>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn-add">➕ ДОБАВИТЬ</button>
                <button type="button" class="btn-print" onclick="window.print()">🖨 ПЕЧАТЬ / PDF</button>
            </div>
        </form>
        <form method="POST" style="display:inline; margin-top: 15px;" onsubmit="return confirm('Удалить ВСЕ записи? Это действие нельзя отменить!')">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn-clear">🗑 ОЧИСТИТЬ ВСЁ</button>
        </form>
    </div>

    <!-- Таблица -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Место</th>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Охват</th>
                    <th class="action-cell">Удал.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['date_label']) ?></td>
                    <td><?= nl2br(htmlspecialchars(str_replace('/', "\n", $event['time_range']))) ?></td>
                    <td><?= nl2br(htmlspecialchars(str_replace('/', "\n", $event['location']))) ?></td>
                    <td><strong style="color:var(--neon-green);text-shadow:0 0 5px var(--neon-green);"><?= nl2br(htmlspecialchars(str_replace('/', "\n", $event['title']))) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars(str_replace('/', "\n", $event['description']))) ?></td>
                    <td><?= nl2br(htmlspecialchars(str_replace('/', "\n", $event['coverage']))) ?></td>
                    <td class="action-cell">
                        <form method="POST" onsubmit="return confirm('Удалить запись?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="delete-btn">×</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--purple-glow);padding:30px;">Нет записей. Добавьте мероприятие выше.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Валидация формы перед отправкой
    document.getElementById('addForm').addEventListener('submit', function(e) {
        const timeStart = document.querySelector('[name="time_start"]').value;
        const timeEnd = document.querySelector('[name="time_end"]').value;
        
        if (timeStart && timeEnd && timeStart >= timeEnd) {
            e.preventDefault();
            alert('Время окончания должно быть позже времени начала!');
            return false;
        }
    });
</script>

</body>
</html>