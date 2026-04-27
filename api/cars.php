<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, title, price, procent, image_url FROM cars ORDER BY id ASC");
    $cars = $stmt->fetchAll();
    
    echo json_encode($cars, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>