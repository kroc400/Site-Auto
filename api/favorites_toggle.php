<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$car_id = $input['car_id'] ?? 0;

if (!$car_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID']);
    exit;
}

// Проверяем, есть ли уже
$stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND car_id = ?");
$stmt->execute([$user_id, $car_id]);
$exists = $stmt->fetch();

if ($exists) {
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND car_id = ?");
    $stmt->execute([$user_id, $car_id]);
    echo json_encode(['success' => true, 'message' => 'Удалено из избранного', 'action' => 'removed']);
} else {
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, car_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $car_id]);
    echo json_encode(['success' => true, 'message' => 'Добавлено в избранное', 'action' => 'added']);
}
?>