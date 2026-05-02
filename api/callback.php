<?php
// api/callback.php

// --- !!! ОБЯЗАТЕЛЬНО ЗАМЕНИТЕ НА ВАШИ ДАННЫЕ !!! ---
$api_key = '4e2eb28ce583afde38e7d9c6436fb826';
$campaign_id = '573230408';
// ---

header('Content-Type: application/json');

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

// Формируем упрощенные данные для запроса
$postData = [
    'public_key' => $api_key,
    'phone' => $phone,
    'campaign_id' => $campaign_id,
    // Все остальные параметры, такие как 'text', 'speaker', удалены
];

// Отправляем запрос к API
$ch = curl_init('https://zvonok.com/manager/cabapi_external/api/v1/phones/call/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Для теста, в продакшене лучше true

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Отвечаем фронтенду
if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Запрос принят, ожидайте звонка']);
} else {
    // Выводим ответ сервера для диагностики
    echo json_encode(['success' => false, 'error' => $response]);
}
?>