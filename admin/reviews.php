<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_review'])) {
    $id = (int)$_POST['id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $stmt = $pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=?");
    $stmt->execute([$rating, $comment, $id]);
    header('Location: reviews.php');
    exit;
}

$reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление отзывами</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Отзывы</h1>
            </div>
            <?php include 'nav.php'; ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Дата</th><th>Пользователь</th><th>Рейтинг</th><th>Комментарий</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td data-label="ID"><?= $review['id'] ?></td>
                            <td data-label="Дата"><?= $review['created_at'] ?></td>
                            <td data-label="Пользователь"><?= htmlspecialchars($review['user_name']) ?></td>
                            <td data-label="Рейтинг">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                    <input type="number" name="rating" value="<?= $review['rating'] ?>" min="1" max="10" style="width: 60px;">
                                    <input type="text" name="comment" value="<?= htmlspecialchars($review['comment']) ?>" style="width: 200px;">
                                    <button type="submit" name="edit_review" class="btn-edit">✏️</button>
                                </form>
                            </td>
                            <td class="actions-cell" data-label="Действия">
                                <button class="btn-delete" data-id="<?= $review['id'] ?>" data-table="reviews" onclick="deleteItem(this)">🗑️</button>
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