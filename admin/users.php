<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи | Админ-панель</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header"><h1>Пользователи</h1><a href="edit_user.php" class="btn-add">➕ Добавить</a></div>
            <div class="admin-nav">
                <a href="cars.php">Автомобили</a>
                <a href="orders.php">Заказы</a>
                <a href="reviews.php">Отзывы</a>
                <a href="users.php" class="active">Пользователи</a>
                <a href="../account.html">На сайт</a>
            </div>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Дата регистрации</th><th>Действия</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td data-label="ID"><?= $user['id'] ?></td>
                        <td data-label="Логин"><?= htmlspecialchars($user['username']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                        <td data-label="Роль"><?= $user['role'] ?></td>
                        <td data-label="Дата"><?= $user['created_at'] ?></td>
                        <td data-label="Действия" class="actions-cell">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn-edit">✏️</a>
                            <button class="btn-delete" data-id="<?= $user['id'] ?>" data-table="users" onclick="deleteItem(this)">🗑️</button>
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
    <script> function deleteItem(btn) { if(confirm('Удалить пользователя?')) window.location.href = `delete.php?table=${btn.dataset.table}&id=${btn.dataset.id}`; } </script>
</body>
</html>