<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

// GET — получаем данные
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT username, email, surname, name, patronymic, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    echo json_encode($user);
    exit;
}

// POST — обновляем данные
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $surname = $input['surname'] ?? '';
    $name = $input['name'] ?? '';
    $patronymic = $input['patronymic'] ?? '';
    $phone = $input['phone'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE users SET surname = ?, name = ?, patronymic = ?, phone = ? WHERE id = ?");
    $stmt->execute([$surname, $name, $patronymic, $phone, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Профиль обновлён']);
    exit;
}
?>