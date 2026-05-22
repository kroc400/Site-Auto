<?php
// api/register.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$captchaAnswer = $input['captcha'] ?? null;

// Проверка капчи
if (!isset($_SESSION['captcha_result']) || $captchaAnswer != $_SESSION['captcha_result']) {
    echo json_encode(['success' => false, 'message' => 'Неправильный ответ капчи']);
    exit;
}

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
    exit;
}

// Проверка на существование пользователя
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином или email уже существует']);
    exit;
}

// Хешируем пароль и сохраняем
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$username, $email, $password_hash]);

// Очищаем капчу из сессии после успешной регистрации
unset($_SESSION['captcha_result']);

echo json_encode(['success' => true, 'message' => 'Регистрация успешна']);
?>