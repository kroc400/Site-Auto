<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Необходимо авторизоваться']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rating = (int)($input['rating'] ?? 0);
$comment = trim($input['comment'] ?? '');

if ($rating < 1 || $rating > 10 || empty($comment)) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректные данные. Рейтинг от 1 до 10, комментарий не пустой.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Пользователь';

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, user_name, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_name, $rating, $comment]);
    echo json_encode(['success' => true, 'message' => 'Спасибо! Ваш отзыв добавлен.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка при сохранении отзыва']);
}
?>