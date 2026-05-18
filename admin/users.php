<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->execute([$role, $id]);

    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $stmt->execute([$new_password, $id]);
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="admin-container">
            <div class="admin-header">
                <h1>Пользователи</h1>
            </div>
            <?php include 'nav.php'; ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="ID"><?= $user['id'] ?></td>
                            <td data-label="Логин"><?= htmlspecialchars($user['username']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                            <td data-label="Роль">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <select name="role">
                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                    </select>
                                    <input type="text" name="new_password" placeholder="Новый пароль (опционально)">
                                    <button type="submit" name="edit_user" class="btn-edit">✏️</button>
                                </form>
                            </td>
                            <td class="actions-cell" data-label="Действия">
                                <button class="btn-delete" data-id="<?= $user['id'] ?>" data-table="users" onclick="deleteItem(this)">🗑️</button>
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