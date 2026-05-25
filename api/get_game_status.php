<?php
// api/get_game_status.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$game_type = $_GET['game_type'] ?? 'race';

try {
    $stmt = $pdo->prepare("SELECT * FROM user_game_attempts WHERE user_id = ? AND game_type = ?");
    $stmt->execute([$user_id, $game_type]);
    $gameData = $stmt->fetch();
    
    if ($gameData) {
        echo json_encode([
            'success' => true,
            'played' => (bool)$gameData['played'],
            'won' => (bool)$gameData['won'],
            'score' => $gameData['score'],
            'promocode' => $gameData['promocode'],
            'promocode_used' => (bool)$gameData['promocode_used']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'played' => false,
            'won' => false,
            'score' => 0,
            'promocode' => null,
            'promocode_used' => false
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>