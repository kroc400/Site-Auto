<?php
// api/save_game_result.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$game_type = $input['game_type'] ?? 'race';
$won = isset($input['won']) ? (int)$input['won'] : 0;
$score = (int)($input['score'] ?? 0);
$promocode = $input['promocode'] ?? null;

try {
    // Проверяем, есть ли уже запись
    $stmt = $pdo->prepare("SELECT * FROM user_game_attempts WHERE user_id = ? AND game_type = ?");
    $stmt->execute([$user_id, $game_type]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Обновляем существующую запись (только если ещё не играл)
        if ($existing['played'] == 0) {
            $stmt = $pdo->prepare("UPDATE user_game_attempts SET played = 1, won = ?, score = ?, promocode = ? WHERE user_id = ? AND game_type = ?");
            $stmt->execute([$won, $score, $promocode, $user_id, $game_type]);
        }
    } else {
        // Создаём новую запись
        $stmt = $pdo->prepare("INSERT INTO user_game_attempts (user_id, game_type, played, won, score, promocode) VALUES (?, ?, 1, ?, ?, ?)");
        $stmt->execute([$user_id, $game_type, $won, $score, $promocode]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>