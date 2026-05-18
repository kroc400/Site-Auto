<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

try {
    // Используем поле stock_quantity из таблицы cars
    $stmt = $pdo->query("SELECT id, title, price_value, stock_quantity, image_url FROM cars WHERE stock_quantity > 0 ORDER BY id ASC");
    $cars = $stmt->fetchAll();
    
    echo json_encode($cars, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>