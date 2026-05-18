<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) die('Не указан ID');
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();
if (!$review) die('Отзыв не найден');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = $_POST['comment'];
    $stmt = $pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=?");
    $stmt->execute([$rating, $comment, $id]);
    header('Location: reviews.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать отзыв</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="form-container">
            <h1>Редактирование отзыва</h1>
            <form method="post">
                <div class="form-group"><label>Оценка (1-10)</label><input type="number" name="rating" min="1" max="10" value="<?= $review['rating'] ?>"></div>
                <div class="form-group"><label>Комментарий</label><textarea name="comment"><?= htmlspecialchars($review['comment']) ?></textarea></div>
                <button type="submit">Сохранить</button>
                <a href="reviews.php">Отмена</a>
            </form>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
</body>
</html>