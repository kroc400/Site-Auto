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

// ========== ОБРАБОТЧИКИ ДЛЯ ПОЛЬЗОВАТЕЛЕЙ ==========

// Обновление данных пользователя (ФИО, логин, email, роль, телефон)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $surname = $_POST['surname'] ?? '';
    $name = $_POST['name'] ?? '';
    $patronymic = $_POST['patronymic'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $phone = $_POST['phone'] ?? '';
    
    // Проверка на уникальность логина и email (исключая текущего пользователя)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetch()) {
        $error = "Пользователь с таким логином или email уже существует";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET surname = ?, name = ?, patronymic = ?, username = ?, email = ?, role = ?, phone = ? WHERE id = ?");
        $stmt->execute([$surname, $name, $patronymic, $username, $email, $role, $phone, $user_id]);
        $success = "Данные пользователя обновлены";
    }
    
    header('Location: admin.php?tab=users' . ($error ? '&error=' . urlencode($error) : '&success=' . urlencode($success)));
    exit;
}

// Сброс пароля пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'] ?? '123456';
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$password_hash, $user_id]);
    
    header('Location: admin.php?tab=users&success=Пароль сброшен на ' . urlencode($new_password));
    exit;
}

// Удаление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Не даём удалить самого себя
    if ($user_id != $_SESSION['user_id']) {
        // Сначала удаляем связанные записи (избранное, отзывы, заказы)
        $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM reviews WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM orders WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $success = "Пользователь удалён";
    } else {
        $error = "Нельзя удалить самого себя";
    }
    
    header('Location: admin.php?tab=users' . ($error ? '&error=' . urlencode($error) : '&success=' . urlencode($success)));
    exit;
}

// ========== ОБРАБОТЧИКИ ДЛЯ ЗАКАЗОВ И АВТО ==========

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
    header('Location: admin.php?tab=orders');
    exit;
}

// Обновление количества автомобиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $car_id = (int)$_POST['car_id'];
    $new_stock = (int)$_POST['stock_quantity'];
    $pdo->prepare("UPDATE cars SET stock_quantity = ? WHERE id = ?")->execute([$new_stock, $car_id]);
    header('Location: admin.php?tab=cars');
    exit;
}

// Сохранение настройки списания
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_decrease'])) {
    $new_value = isset($_POST['decrease_enabled']) ? '1' : '0';
    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('decrease_stock_on_confirm', ?)")->execute([$new_value]);
    header('Location: admin.php');
    exit;
}

// ========== ПОЛУЧЕНИЕ ДАННЫХ ==========
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
$cars = $pdo->query("SELECT id, title, stock_quantity, price_value FROM cars ORDER BY id")->fetchAll();
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id")->fetchAll();

$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'decrease_stock_on_confirm'");
$decrease_enabled = ($stmt->fetch()['setting_value'] ?? '0') == '1';

$currentYear = date('Y');
$yearlyRevenue = getYearlyRevenue($pdo, $currentYear);
$totalOrders = getTotalOrdersCount($pdo, $currentYear);
$popularCar = getMostPopularCar($pdo, $currentYear);

// Активная вкладка
$activeTab = $_GET['tab'] ?? 'stats';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="./styles.css">
    <style>
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 12px;
        }
        .tab-btn {
            padding: 10px 25px;
            background: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            background: #ddd;
        }
        .tab-btn.active {
            background: #e63946;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        .users-table th, .users-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .users-table th {
            background: #1a1a1a;
            color: white;
        }
        .add-user-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .add-user-form input, .add-user-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .add-user-form button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-reset {
            background: #ff9800;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-delete {
            background: #e63946;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .role-select {
            padding: 4px;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .add-user-form {
                flex-direction: column;
            }
            .add-user-form input, .add-user-form select, .add-user-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <a href="account.html" class="switch-to-user">← Переключиться на пользовательский интерфейс</a>
            <h1>Админ-панель</h1>

            <!-- Вкладки -->
            <div class="admin-tabs">
                <button class="tab-btn <?= $activeTab == 'stats' ? 'active' : '' ?>" data-tab="stats">📊 Статистика</button>
                <button class="tab-btn <?= $activeTab == 'settings' ? 'active' : '' ?>" data-tab="settings">⚙️ Настройки</button>
                <button class="tab-btn <?= $activeTab == 'cars' ? 'active' : '' ?>" data-tab="cars">🚗 Автомобили</button>
                <button class="tab-btn <?= $activeTab == 'orders' ? 'active' : '' ?>" data-tab="orders">📦 Заказы</button>
                <button class="tab-btn <?= $activeTab == 'users' ? 'active' : '' ?>" data-tab="users">👥 Пользователи</button>
            </div>

            <!-- Вкладка: Статистика -->
            <div id="tab-stats" class="tab-content <?= $activeTab == 'stats' ? 'active' : '' ?>">
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
            </div>

            <!-- Вкладка: Настройки -->
            <div id="tab-settings" class="tab-content <?= $activeTab == 'settings' ? 'active' : '' ?>">
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
            </div>

            <!-- Вкладка: Автомобили -->
            <div id="tab-cars" class="tab-content <?= $activeTab == 'cars' ? 'active' : '' ?>">
                <div class="admin-section">
                    <h2>Управление автомобилями</h2>
                    <div class="table-responsive">
                        <table class="cars-table">
                            <thead><tr><th>ID</th><th>Название</th><th>Цена</th><th>Количество</th><th>Действия</th></td></thead>
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
                    <div style="margin-bottom: 20px; text-align: right;">
                        <a href="admin_add_car.php" style="background: #4CAF50; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">➕ Добавить автомобиль</a>
                    </div>
                </div>
            </div>

            <!-- Вкладка: Заказы -->
            <div id="tab-orders" class="tab-content <?= $activeTab == 'orders' ? 'active' : '' ?>">
                <div class="admin-section">
                    <h2>Заказы</h2>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr><th>ID</th><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Автомобиль</th><th>Цена</th><th>Статус</th><th>Списано</th></tr>
                            </thead>
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Вкладка: Пользователи -->
            <div id="tab-users" class="tab-content <?= $activeTab == 'users' ? 'active' : '' ?>">
                <div class="admin-section">
                    <h2>👥 Управление пользователями</h2>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                            ✅ <?= htmlspecialchars($_GET['success']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                            ❌ <?= htmlspecialchars($_GET['error']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ФИО</th>
                                    <th>Логин</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Роль</th>
                                    <th>Дата регистрации</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Получаем всех пользователей с дополнительными полями
                                $users = $pdo->query("SELECT id, username, email, role, created_at, surname, name, patronymic, phone FROM users ORDER BY id")->fetchAll();
                                foreach ($users as $user): 
                                ?>
                                <tr id="user-row-<?= $user['id'] ?>">
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <td data-label="ID"><?= $user['id'] ?></td>
                                        <td data-label="ФИО">
                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                <input type="text" name="surname" value="<?= htmlspecialchars($user['surname'] ?? '') ?>" placeholder="Фамилия" style="width: 100%; padding: 4px;">
                                                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Имя" style="width: 100%; padding: 4px;">
                                                <input type="text" name="patronymic" value="<?= htmlspecialchars($user['patronymic'] ?? '') ?>" placeholder="Отчество" style="width: 100%; padding: 4px;">
                                            </div>
                                        </td>
                                        <td data-label="Логин">
                                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required style="width: 100%; padding: 4px;">
                                        </td>
                                        <td data-label="Email">
                                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required style="width: 100%; padding: 4px;">
                                        </td>
                                        <td data-label="Телефон">
                                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+7 (XXX) XXX-XX-XX" style="width: 100%; padding: 4px;">
                                        </td>
                                        <td data-label="Роль">
                                            <select name="role" style="width: 100%; padding: 4px;">
                                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                            </select>
                                        </td>
                                        <td data-label="Дата регистрации"><?= $user['created_at'] ?></td>
                                        <td data-label="Действия" style="white-space: nowrap;">
                                            <button type="submit" name="update_user" style="background: #4CAF50; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; margin-bottom: 5px;">💾 Сохранить</button>
                                    </form>
                                    <form method="post" style="display: inline-block; margin-left: 5px;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="new_password" value="123456">
                                        <button type="submit" name="reset_password" class="btn-reset" onclick="return confirm('Сбросить пароль пользователя на 123456?')" style="background: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">🔑 Сброс пароля</button>
                                    </form>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="post" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn-delete" onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')" style="background: #e63946; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">🗑️ Удалить</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px; margin-left: 5px;">(Вы)</span>
                                    <?php endif; ?>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer></footer>
    
    <script src="header.js" type="module"></script>
    <script src="footer.js" type="module"></script>
    <script src="backToTopButton.js"></script>
    
    <script>
        // Переключение вкладок
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                // Обновляем URL без перезагрузки
                window.history.pushState({}, '', '?tab=' + tabId);
                // Переключаем активные классы у кнопок
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // Переключаем активные классы у контента
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
        
        // Добавление пользователя через AJAX
        document.getElementById('addUserBtn')?.addEventListener('click', async () => {
            const username = document.getElementById('new_username').value.trim();
            const email = document.getElementById('new_email').value.trim();
            const password = document.getElementById('new_password').value.trim();
            const role = document.getElementById('new_role').value;
            
            if (!username || !email) {
                alert('Заполните логин и email');
                return;
            }
            
            const finalPassword = password || '123456';
            
            try {
                const response = await fetch('/api/add_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, email, password: finalPassword, role })
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Пользователь добавлен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.message);
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка соединения');
            }
        });
    </script>
</body>
</html>