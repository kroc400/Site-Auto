<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}
require_once '../config/database.php';
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$user = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) die('Пользователь не найден');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'] ?? '';
    if ($isEdit) {
        $sql = "UPDATE users SET username=?, email=?, role=?";
        $params = [$username, $email, $role];
        if (!empty($new_password)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password_hash=?";
            $params[] = $hash;
        }
        $sql .= " WHERE id=?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $hash = password_hash($new_password ?: '123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,?)");
        $stmt->execute([$username, $email, $hash, $role]);
    }
    header('Location: users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Редактировать' : 'Добавить' ?> пользователя</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header></header>
    <main class="content">
        <div class="form-container">
            <h1><?= $isEdit ? 'Редактирование пользователя' : 'Новый пользователь' ?></h1>
            <form method="post">
                <div class="form-group"><label>Логин</label><input type="text" name="username" required value="<?= $isEdit ? htmlspecialchars($user['username']) : '' ?>"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?= $isEdit ? htmlspecialchars($user['email']) : '' ?>"></div>
                <div class="form-group"><label>Роль</label>
                    <select name="role">
                        <option value="user" <?= $isEdit && $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                        <option value="admin" <?= $isEdit && $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                    </select>
                </div>
                <div class="form-group"><label>Новый пароль (оставьте пустым, чтобы не менять)</label><input type="password" name="new_password" placeholder="Новый пароль"></div>
                <button type="submit">Сохранить</button>
                <a href="users.php">Отмена</a>
            </form>
        </div>
    </main>
    <footer></footer>
    <script src="../header.js" type="module"></script>
    <script src="../footer.js" type="module"></script>
</body>
</html>