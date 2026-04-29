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
    $stmt = $pdo->prepare("SELECT id, title, price_value, procent, equipment, dimensions FROM cars WHERE id = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    
    if ($car) {
        $car['equipment'] = json_decode($car['equipment'], true);
        $car['dimensions'] = json_decode($car['dimensions'], true);
        echo json_encode($car, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => 'Автомобиль не найден']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>