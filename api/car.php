<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Неверный ID']);
    exit;
}

try {
    // Получаем данные модели + stock_quantity
    $stmt = $pdo->prepare("SELECT id, title, price_value, stock_quantity, procent, image_url, color_images, equipment, dimensions FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        echo json_encode(['error' => 'Автомобиль не найден']);
        exit;
    }
    
    // Декодируем JSON-поля
    $car['equipment'] = json_decode($car['equipment'], true);
    $car['dimensions'] = json_decode($car['dimensions'], true);
    $car['color_images'] = json_decode($car['color_images'], true);
    
    echo json_encode($car, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>