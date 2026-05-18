<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}
require_once 'config/database.php';

// ========== ПОЛЬЗОВАТЕЛЬСКИЕ ФУНКЦИИ ДЛЯ СТАТИСТИКИ ==========
function getMonthlyRevenue($pdo, $year, $month) {
    $stmt = $pdo->prepare("
        SELECT SUM(price_value) as total 
        FROM orders 
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? 
        AND status IN ('processed', 'completed')
    ");
    $stmt->execute([$year, $month]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getYearlyRevenue($pdo, $year) {
    $stmt = $pdo->prepare("
        SELECT SUM(price_value) as total 
        FROM orders 
        WHERE YEAR(created_at) = ? 
        AND status IN ('processed', 'completed')
    ");
    $stmt->execute([$year]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getTotalOrdersCount($pdo, $year = null) {
    if ($year) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE YEAR(created_at) = ? AND status IN ('processed', 'completed')");
        $stmt->execute([$year]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM orders WHERE status IN ('processed', 'completed')");
    }
    return $stmt->fetch()['cnt'];
}

function getMostPopularCar($pdo, $year) {
    $stmt = $pdo->prepare("
        SELECT car_title, COUNT(*) as cnt 
        FROM orders 
        WHERE YEAR(created_at) = ? AND status IN ('processed', 'completed')
        GROUP BY car_title 
        ORDER BY cnt DESC 
        LIMIT 1
    ");
    $stmt->execute([$year]);
    return $stmt->fetch();
}
// =========================================================

// Создаём таблицу settings, если её нет
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
)");
$pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('decrease_stock_on_confirm', '0')");

// Обработка изменения статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'decrease_stock_on_confirm'");
    $setting = $stmt->fetch();
    $decrease_enabled = ($setting && $setting['setting_value'] == '1');

    $stmt = $pdo->prepare("SELECT status, stock_decreased, car_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order) {
        if ($new_status === 'processed' && $decrease_enabled && !$order['stock_decreased']) {
            $pdo->prepare("UPDATE cars SET stock_quantity = stock_quantity - 1 WHERE id = ? AND stock_quantity > 0")->execute([$order['car_id']]);
            $pdo->prepare("UPDATE orders SET stock_decreased = 1, status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        } else {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        }
    }
    header('Location: admin.php');
    exit;
}

// Обновление количества автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $car_id = (int)$_POST['car_id'];
    $new_stock = (int)$_POST['stock_quantity'];
    $pdo->prepare("UPDATE cars SET stock_quantity = ? WHERE id = ?")->execute([$new_stock, $car_id]);
    header('Location: admin.php');
    exit;
}

// Сохранение настройки списания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_decrease'])) {
    $new_value = isset($_POST['decrease_enabled']) ? '1' : '0';
    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('decrease_stock_on_confirm', ?)")->execute([$new_value]);
    header('Location: admin.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$cars = $pdo->query("SELECT id, title, stock_quantity, price_value FROM cars ORDER BY id")->fetchAll();
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'decrease_stock_on_confirm'");
$decrease_enabled = ($stmt->fetch()['setting_value'] ?? '0') == '1';

$currentYear = date('Y');
$yearlyRevenue = getYearlyRevenue($pdo, $currentYear);
$totalOrders = getTotalOrdersCount($pdo, $currentYear);
$popularCar = getMostPopularCar($pdo, $currentYear);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="./styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <a href="account.html" class="switch-to-user">← Переключиться на пользовательский интерфейс</a>
            <h1>Админ-панель</h1>

            <!-- Блок статистики -->
            <div class="admin-section">
                <h2>Статистика продаж за <?= $currentYear ?> год</h2>
                
                <div class="stats-grid">
                    <div class="stats-card">
                        <h3>Общая выручка</h3>
                        <div class="value"><?= number_format($yearlyRevenue, 0, '', ' ') ?> ₽</div>
                    </div>
                    <div class="stats-card">
                        <h3>Количество заказов</h3>
                        <div class="value"><?= $totalOrders ?></div>
                    </div>
                    <div class="stats-card">
                        <h3>Самый популярный автомобиль</h3>
                        <?php if ($popularCar && $popularCar['cnt'] > 0): ?>
                            <div class="value" style="font-size: 18px;"><?= htmlspecialchars($popularCar['car_title']) ?></div>
                            <div style="margin-top: 5px; color: #888;">Заказов: <?= $popularCar['cnt'] ?></div>
                        <?php else: ?>
                            <div class="value" style="font-size: 16px;">Нет заказов</div>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="monthly-table">
                    <thead>
                        <tr><th>Месяц</th><th>Выручка</th><th>Количество заказов</th></tr>
                    </thead>
                    <tbody>
                        <?php for ($month = 1; $month <= 12; $month++): 
                            $monthName = date('F', mktime(0, 0, 0, $month, 1));
                            $monthlyRevenue = getMonthlyRevenue($pdo, $currentYear, $month);
                            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM orders WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND status != 'cancelled'");
                            $stmt->execute([$currentYear, $month]);
                            $monthlyOrders = $stmt->fetch()['cnt'];
                        ?>
                        <tr>
                            <td data-label="Месяц"><?= $monthName ?></td>
                            <td data-label="Выручка"><?= $monthlyRevenue > 0 ? number_format($monthlyRevenue, 0, '', ' ') . ' ₽' : '—' ?></td>
                            <td data-label="Заказы"><?= $monthlyOrders ?></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Блок настроек (кнопка внутри) -->
            <div class="admin-section">
                <h2>Настройки</h2>
                <div class="settings-panel">
                    <form method="post">
                        <label>
                            <input type="checkbox" name="decrease_enabled" value="1" <?= $decrease_enabled ? 'checked' : '' ?>>
                            Уменьшать количество автомобилей при подтверждении заказа (статус "Подтверждён")
                        </label>
                        <button type="submit" name="toggle_decrease">Сохранить</button>
                    </form>
                </div>
            </div>

            <!-- Автомобили -->
            <div class="admin-section">
                <h2>Управление автомобилями</h2>
                <div class="table-responsive">
                    <table class="cars-table">
                        <thead><tr><th>ID</th><th>Название</th><th>Цена</th><th>Количество</th><th>Действия</th></tr></thead>
                        <tbody>
                            <?php foreach ($cars as $car): ?>
                            <tr>
                                <td data-label="ID"><?= $car['id'] ?></td>
                                <td data-label="Название"><?= htmlspecialchars($car['title']) ?></td>
                                <td data-label="Цена"><?= number_format($car['price_value'], 0, '', ' ') ?> ₽</td>
                                <td data-label="Количество">
                                    <form method="post" style="display: inline-flex; gap: 5px; align-items: center;">
                                        <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                        <input type="number" name="stock_quantity" value="<?= $car['stock_quantity'] ?>" class="stock-input" min="0">
                                        <button type="submit" name="update_stock">Обновить</button>
                                    </form>
                                </td>
                                <td data-label="Действия"><a href="car_template.html?id=<?= $car['id'] ?>" target="_blank">Просмотр</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Заказы -->
            <div class="admin-section">
                <h2>Заказы</h2>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead><tr><th>ID</th><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Автомобиль</th><th>Цена</th><th>Статус</th><th>Списано</th></tr></thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td data-label="ID"><?= $order['id'] ?></td>
                                <td data-label="Дата"><?= $order['created_at'] ?></td>
                                <td data-label="Клиент"><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td data-label="Телефон"><?= htmlspecialchars($order['customer_phone']) ?></td>
                                <td data-label="Автомобиль"><?= htmlspecialchars($order['car_title']) ?></td>
                                <td data-label="Цена"><?= htmlspecialchars($order['car_price']) ?></td>
                                <td data-label="Статус" class="status-<?= $order['status'] ?>">
                                    <form method="post" style="display: inline-flex; gap: 5px; align-items: center;">
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
                                <td data-label="Списано"><?= $order['stock_decreased'] ? 'Да' : 'Нет' ?></td>
                                <td data-label="Действия">
                                    <!-- можно добавить удаление, если нужно -->
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <footer></footer>
    <script src="header.js" type="module"></script>
    <script src="footer.js" type="module"></script>
    <script src="backToTopButton.js"></script>
</body>
</html>