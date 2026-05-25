<?php
// api/reset_password.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$new_password = $input['new_password'] ?? '';

if (empty($token) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Не все поля заполнены']);
    exit;
}

// Проверяем токен
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    echo json_encode(['success' => false, 'message' => 'Недействительная или просроченная ссылка для восстановления']);
    exit;
}

$email = $reset['email'];

// Обновляем пароль пользователя
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$password_hash, $email]);

// Помечаем токен как использованный
$stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
$stmt->execute([$token]);

echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён! Перенаправление на страницу входа...']);
?>