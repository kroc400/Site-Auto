<?php
// api/callback.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// --- НАСТРОЙКИ ZVONOK ---
$api_key = '4e2eb28ce583afde38e7d9c6436fb826';
$campaign_id = '573230408';
// ---

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Обязательные поля
$phone = $input['phone'] ?? null;
$car_id = $input['car_id'] ?? null;
$car_title = $input['car_title'] ?? null;
$car_price = $input['car_price'] ?? null;

// Дополнительные поля
$name = !empty($input['name']) ? $input['name'] : 'Клиент';
$user_id = $_SESSION['user_id'] ?? null;

// Валидация
if (!$phone || !$car_id || !$car_title || !$car_price) {
    http_response_code(400);
    echo json_encode(['error' => 'Заполните все обязательные поля (телефон, авто)']);
    exit;
}

// 1. СОХРАНЯЕМ ЗАКАЗ В БД
try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, customer_name, customer_phone, car_id, car_title, car_price, status)
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");
    $stmt->execute([$user_id, $name, $phone, $car_id, $car_title, $car_price]);
    $order_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сохранения заказа: ' . $e->getMessage()]);
    exit;
}

// 2. ОТПРАВЛЯЕМ ЗАПРОС НА ЗВОНОК
$messageText = "Здравствуйте, $name! Вы оставили заявку на автомобиль $car_title стоимостью $car_price рублей. Наш менеджер свяжется с вами в ближайшее время. Номер вашего заказа: $order_id.";

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

// 3. ОТВЕЧАЕМ КЛИЕНТУ
if ($httpCode === 200) {
    echo json_encode([
        'success' => true,
        'message' => 'Заявка принята! Номер заказа: ' . $order_id . '. Ожидайте звонка.',
        'order_id' => $order_id
    ]);
} else {
    // Звонок не удался, но заказ сохранён – администратор увидит и обработает вручную
    echo json_encode([
        'success' => true, // всё равно true, чтобы клиент не паниковал
        'message' => 'Заявка принята! Номер заказа: ' . $order_id . '. С вами свяжутся в ближайшее время.',
        'order_id' => $order_id,
        'call_error' => $response // для отладки
    ]);
}
?>