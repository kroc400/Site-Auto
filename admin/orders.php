<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $stmt = $pdo->prepare("SELECT status, car_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if ($order && $new_status === 'processed') {
        $stmt = $pdo->prepare("UPDATE cars SET stock_quantity = stock_quantity - 1 WHERE id = ? AND stock_quantity > 0");
        $stmt->execute([$order['car_id']]);
    }
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    header('Location: orders.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Заказы</h1>
            </div>
            <?php include 'nav.php'; ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Автомобиль</th><th>Цена</th><th>Статус</th><th>Действия</th></tr>
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
                            <td class="status-<?= $order['status'] ?>" data-label="Статус">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                        <option value="processed" <?= $order['status'] === 'processed' ? 'selected' : '' ?>>Подтверждён</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                                    </select>
                                </form>
                            </td>
                            <td class="actions-cell" data-label="Действия">
                                <button class="btn-delete" data-id="<?= $order['id'] ?>" data-table="orders" onclick="deleteItem(this)">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
    <script src="admin.js"></script>
</body>
</html>