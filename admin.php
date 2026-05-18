<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
require_once 'config/database.php';

// Обработка изменения статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    // Проверяем, включена ли функция списания
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'decrease_stock_on_confirm'");
    $setting = $stmt->fetch();
    $decrease_enabled = ($setting && $setting['setting_value'] == '1');

    // Получаем текущий статус и флаг списания, а также car_id
    $stmt = $pdo->prepare("SELECT status, stock_decreased, car_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        // Если статус меняется на 'processed' и списание включено и ещё не списано
        if ($new_status === 'processed' && $decrease_enabled && !$order['stock_decreased']) {
            // Уменьшаем количество у автомобиля
            $stmt2 = $pdo->prepare("UPDATE cars SET stock_quantity = stock_quantity - 1 WHERE id = ? AND stock_quantity > 0");
            $stmt2->execute([$order['car_id']]);
            // Отмечаем, что списание выполнено
            $stmt2 = $pdo->prepare("UPDATE orders SET stock_decreased = 1, status = ? WHERE id = ?");
            $stmt2->execute([$new_status, $order_id]);
        } else {
            // Просто обновляем статус без списания
            $stmt2 = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt2->execute([$new_status, $order_id]);
        }
    }
    header('Location: admin.php');
    exit;
}

// Обработка обновления количества автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $car_id = (int)$_POST['car_id'];
    $new_stock = (int)$_POST['stock_quantity'];
    $stmt = $pdo->prepare("UPDATE cars SET stock_quantity = ? WHERE id = ?");
    $stmt->execute([$new_stock, $car_id]);
    header('Location: admin.php');
    exit;
}

// Обработка настройки списания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_decrease'])) {
    $new_value = isset($_POST['decrease_enabled']) ? '1' : '0';
    $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('decrease_stock_on_confirm', ?)");
    $stmt->execute([$new_value]);
    header('Location: admin.php');
    exit;
}

// Получаем все заказы
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

// Получаем все автомобили
$cars = $pdo->query("SELECT id, title, stock_quantity, price_value FROM cars ORDER BY id")->fetchAll();

// Получаем текущее значение настройки
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'decrease_stock_on_confirm'");
$setting = $stmt->fetch();
$decrease_enabled = ($setting && $setting['setting_value'] == '1');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="./styles.css">
    <style>
        /* Дополнительные стили для админки */
        .admin-container {
            margin: 64px 13.3vw;
        }
        .admin-section {
            background: #f5f5f5;
            border-radius: 25px;
            padding: 20px;
            margin-bottom: 40px;
        }
        .admin-section h2 {
            margin-top: 0;
            font-size: 28px;
        }
        .orders-table, .cars-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        .orders-table th, .orders-table td,
        .cars-table th, .cars-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .orders-table th, .cars-table th {
            background: #1a1a1a;
            color: white;
        }
        .status-new { color: #e63946; font-weight: bold; }
        .status-processed { color: #ff9800; }
        .status-completed { color: #4CAF50; }
        .status-cancelled { color: #999; }
        .switch-to-user {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #e63946;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
        .switch-to-user:hover {
            background: #c1121f;
        }
        .settings-panel {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .stock-input {
            width: 70px;
        }
    </style>
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <a href="account.html" class="switch-to-user">← Переключиться на пользовательский интерфейс</a>
            <h1>Админ-панель</h1>

            <!-- Настройки -->
            <div class="admin-section">
                <h2>Настройки</h2>
                <div class="settings-panel">
                    <form method="post">
                        <label>
                            <input type="checkbox" name="decrease_enabled" value="1" <?= $decrease_enabled ? 'checked' : '' ?>>
                            Уменьшать количество автомобилей при подтверждении заказа (статус "Подтверждён")
                        </label>
                        <button type="submit" name="toggle_decrease" class="car-card-button" style="width: auto; padding: 5px 15px; margin-left: 20px;">Сохранить</button>
                    </form>
                </div>
            </div>

            <!-- Управление автомобилями -->
            <div class="admin-section">
                <h2>Управление автомобилями</h2>
                <table class="cars-table">
                    <thead>
                        <tr><th>ID</th><th>Название</th><th>Цена</th><th>Количество</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                        <tr>
                            <td><?= $car['id'] ?></td>
                            <td><?= htmlspecialchars($car['title']) ?></td>
                            <td><?= number_format($car['price_value'], 0, '', ' ') ?> ₽</td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                    <input type="number" name="stock_quantity" value="<?= $car['stock_quantity'] ?>" class="stock-input" min="0">
                                    <button type="submit" name="update_stock">Обновить</button>
                                </form>
                            </td>
                            <td>
                                <a href="car_template.html?id=<?= $car['id'] ?>" target="_blank">Просмотр</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Заказы -->
            <div class="admin-section">
                <h2>Заказы</h2>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Автомобиль</th><th>Цена</th><th>Статус</th><th>Списано</th><th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= $order['created_at'] ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                            <td><?= htmlspecialchars($order['car_title']) ?></td>
                            <td><?= htmlspecialchars($order['car_price']) ?></td>
                            <td class="status-<?= $order['status'] ?>"><?= $order['status'] ?></td>
                            <td><?= $order['stock_decreased'] ? 'Да' : 'Нет' ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status">
                                        <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                        <option value="processed" <?= $order['status'] === 'processed' ? 'selected' : '' ?>>Подтверждён</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                                    </select>
                                    <button type="submit">Обновить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer></footer>
    <script src="header.js" type="module"></script>
    <script src="footer.js" type="module"></script>
</body>
</html>