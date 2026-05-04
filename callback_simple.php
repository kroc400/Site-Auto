<?php
// api/callback_simple.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// --- НАСТРОЙКИ ZVONOK ---
$api_key = 'ВАШ_API_KEY';
$campaign_id = 'ВАШ_ID_КАМПАНИИ'; // Для простых звонков
// ---

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? null;

if (!$phone) {
    http_response_code(400);
    echo json_encode(['error' => 'Не указан номер телефона']);
    exit;
}

// Сохраняем в отдельную таблицу простых заявок (или в ту же orders, но без car_id)
try {
    // Проверяем существование таблицы simple_requests, если нет – создаём
    $pdo->exec("CREATE TABLE IF NOT EXISTS simple_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("INSERT INTO simple_requests (phone) VALUES (?)");
    $stmt->execute([$phone]);
} catch (PDOException $e) {
    // Логируем ошибку, но не прерываем выполнение
    error_log('Ошибка сохранения заявки: ' . $e->getMessage());
}

// Отправляем звонок
$messageText = "Здравствуйте! Вам звонят из автосалона. Наш менеджер свяжется с вами в ближайшее время.";
$postData = [
    'public_key' => $api_key,
    'phone' => $phone,
    'campaign_id' => $campaign_id,
    'text' => $messageText
];

$ch = curl_init('https://zvonok.com/manager/cabapi_external/api/v1/phones/call/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Спасибо! Ожидайте звонка.']);
} else {
    echo json_encode(['success' => false, 'error' => 'Не удалось инициировать звонок. Попробуйте позже.']);
}
?>