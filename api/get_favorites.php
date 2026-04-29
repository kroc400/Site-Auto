<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.price_value, c.procent, c.image_url, c.equipment, c.dimensions
    FROM cars c
    JOIN favorites f ON c.id = f.car_id
    WHERE f.user_id = ?
    ORDER BY f.added_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

foreach ($favorites as &$car) {
    $car['equipment'] = json_decode($car['equipment'], true);
    $car['dimensions'] = json_decode($car['dimensions'], true);
}

echo json_encode($favorites, JSON_UNESCAPED_UNICODE);
?>