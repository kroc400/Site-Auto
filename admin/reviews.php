<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

$reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы | Админ-панель</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header"><h1>Отзывы</h1></div>
            <div class="admin-nav">
                <a href="cars.php">Автомобили</a>
                <a href="orders.php">Заказы</a>
                <a href="reviews.php" class="active">Отзывы</a>
                <a href="users.php">Пользователи</a>
                <a href="../account.html">На сайт</a>
            </div>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Пользователь</th><th>Оценка</th><th>Комментарий</th><th>Дата</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td data-label="ID"><?= $review['id'] ?></td>
                        <td data-label="Пользователь"><?= htmlspecialchars($review['user_name']) ?></td>
                        <td data-label="Оценка"><?= $review['rating'] ?>/10</td>
                        <td data-label="Комментарий"><?= htmlspecialchars($review['comment']) ?></td>
                        <td data-label="Дата"><?= $review['created_at'] ?></td>
                        <td data-label="Действия" class="actions-cell">
                            <a href="edit_review.php?id=<?= $review['id'] ?>" class="btn-edit">✏️</a>
                            <button class="btn-delete" data-id="<?= $review['id'] ?>" data-table="reviews" onclick="deleteItem(this)">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
    <script>
        function deleteItem(btn) { if(confirm('Удалить?')) window.location.href = `delete.php?table=${btn.dataset.table}&id=${btn.dataset.id}`; }
    </script>
</body>
</html>