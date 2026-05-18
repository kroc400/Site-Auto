<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$car = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    if (!$car) { die('Автомобиль не найден'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $price_value = (int)($_POST['price_value'] ?? 0);
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $procent = $_POST['procent'] ?? '';
    $image_url = $_POST['image_url'] ?? '';

    if ($isEdit) {
        $stmt = $pdo->prepare("UPDATE cars SET title=?, price_value=?, stock_quantity=?, procent=?, image_url=? WHERE id=?");
        $stmt->execute([$title, $price_value, $stock_quantity, $procent, $image_url, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cars (title, price_value, stock_quantity, procent, image_url) VALUES (?,?,?,?,?)");
        $stmt->execute([$title, $price_value, $stock_quantity, $procent, $image_url]);
    }
    header('Location: cars.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактировать' : 'Добавить' ?> автомобиль</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="form-container">
            <h1><?= $isEdit ? 'Редактирование автомобиля' : 'Новый автомобиль' ?></h1>
            <form method="post">
                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" name="title" required value="<?= htmlspecialchars($isEdit ? $car['title'] : '') ?>">
                </div>
                <div class="form-group">
                    <label>Цена (число) *</label>
                    <input type="number" name="price_value" required value="<?= $isEdit ? $car['price_value'] : '' ?>">
                </div>
                <div class="form-group">
                    <label>Количество в наличии</label>
                    <input type="number" name="stock_quantity" value="<?= $isEdit ? $car['stock_quantity'] : 1 ?>">
                </div>
                <div class="form-group">
                    <label>Процент / кредит</label>
                    <input type="text" name="procent" value="<?= htmlspecialchars($isEdit ? $car['procent'] : '') ?>">
                </div>
                <div class="form-group">
                    <label>URL изображения</label>
                    <input type="text" name="image_url" value="<?= htmlspecialchars($isEdit ? $car['image_url'] : '') ?>">
                </div>
                <button type="submit">Сохранить</button>
                <a href="cars.php">Отмена</a>
            </form>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
</body>
</html>