<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Не указан ID заказа');

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) die('Заказ не найден');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = $_POST['customer_name'] ?? '';
    $customer_phone = $_POST['customer_phone'] ?? '';
    $status = $_POST['status'] ?? 'new';
    $car_title = $_POST['car_title'] ?? '';
    $car_price = $_POST['car_price'] ?? '';
    $stmt = $pdo->prepare("UPDATE orders SET customer_name=?, customer_phone=?, status=?, car_title=?, car_price=? WHERE id=?");
    $stmt->execute([$customer_name, $customer_phone, $status, $car_title, $car_price, $id]);
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать заказ</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="form-container">
            <h1>Редактирование заказа #<?= $id ?></h1>
            <form method="post">
                <div class="form-group"><label>Имя клиента</label><input type="text" name="customer_name" value="<?= htmlspecialchars($order['customer_name']) ?>"></div>
                <div class="form-group"><label>Телефон</label><input type="text" name="customer_phone" value="<?= htmlspecialchars($order['customer_phone']) ?>"></div>
                <div class="form-group"><label>Автомобиль</label><input type="text" name="car_title" value="<?= htmlspecialchars($order['car_title']) ?>"></div>
                <div class="form-group"><label>Цена</label><input type="text" name="car_price" value="<?= htmlspecialchars($order['car_price']) ?>"></div>
                <div class="form-group"><label>Статус</label>
                    <select name="status">
                        <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                        <option value="processed" <?= $order['status'] === 'processed' ? 'selected' : '' ?>>Подтверждён</option>
                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Выполнен</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                    </select>
                </div>
                <button type="submit">Сохранить</button>
                <a href="orders.php">Отмена</a>
            </form>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
</body>
</html>