<?php
// api/forgot_password.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Введите email']);
    exit;
}

// Проверяем, существует ли пользователь с таким email
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким email не найден']);
    exit;
}

// Генерируем уникальный токен
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Сохраняем токен в БД
$stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$email, $token, $expires_at]);

// Формируем ссылку для восстановления
$reset_url = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.html?token=" . $token;

echo json_encode([
    'success' => true,
    'message' => 'Ссылка для восстановления пароля создана!',
    'reset_url' => $reset_url
]);
?>