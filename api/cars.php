<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, title, price, procent, equipment, dimensions FROM cars ORDER BY id ASC");
    $cars = $stmt->fetchAll();
    
    // Преобразуем JSON-строки в объекты (как было в вашем файле)
    foreach ($cars as &$car) {
        $car['equipment'] = json_decode($car['equipment'], true);
        $car['dimensions'] = json_decode($car['dimensions'], true);
    }
    
    echo json_encode($cars, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>