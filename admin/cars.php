<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

$stmt = $pdo->query("SELECT id, title, price_value, stock_quantity, procent, image_url FROM cars ORDER BY id DESC");
$cars = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление автомобилями</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Автомобили</h1>
                <a href="edit_car.php" class="btn-add">➕ Добавить автомобиль</a>
            </div>
            <?php include 'nav.php'; ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Название</th><th>Цена</th><th>В наличии</th><th>Процент</th><th>Изображение</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                        <tr>
                            <td data-label="ID"><?= $car['id'] ?></td>
                            <td data-label="Название"><?= htmlspecialchars($car['title']) ?></td>
                            <td data-label="Цена"><?= number_format($car['price_value'], 0, '', ' ') ?> ₽</td>
                            <td data-label="В наличии"><?= $car['stock_quantity'] ?></td>
                            <td data-label="Процент"><?= htmlspecialchars($car['procent']) ?></td>
                            <td data-label="Изображение"><img src="<?= htmlspecialchars($car['image_url']) ?>" width="50" onerror="this.src='/images/placeholder.png'"></td>
                            <td class="actions-cell" data-label="Действия">
                                <a href="edit_car.php?id=<?= $car['id'] ?>" class="btn-edit">✏️</a>
                                <button class="btn-delete" data-id="<?= $car['id'] ?>" data-table="cars" onclick="deleteItem(this)">🗑️</button>
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