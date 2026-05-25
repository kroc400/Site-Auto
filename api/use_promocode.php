<?php
// api/use_promocode.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$promocode = $input['promocode'] ?? '';
$game_type = $input['game_type'] ?? 'race';

try {
    // Проверяем существование и валидность промокода
    $stmt = $pdo->prepare("SELECT * FROM user_game_attempts WHERE user_id = ? AND game_type = ? AND promocode = ? AND promocode_used = 0");
    $stmt->execute([$user_id, $game_type, $promocode]);
    $gameData = $stmt->fetch();
    
    if ($gameData) {
        // Помечаем промокод как использованный
        $stmt = $pdo->prepare("UPDATE user_game_attempts SET promocode_used = 1 WHERE user_id = ? AND game_type = ?");
        $stmt->execute([$user_id, $game_type]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Промокод активирован! Скидка 10% применена к вашему заказу.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Неверный или уже использованный промокод'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>