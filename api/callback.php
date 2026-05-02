<?php
// --- !!! ОБЯЗАТЕЛЬНО ЗАМЕНИТЕ НА ВАШИ ДАННЫЕ !!! ---
$api_key = '4e2eb28ce583afde38e7d9c6436fb826'; // Из первого шага
$campaign_id = '573230408'; // Из первого шага для простого звонка
// ---

header('Content-Type: application/json');

// Разрешаем принимать POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

// Получаем данные из тела запроса
$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? null;
$name = $input['name'] ?? null; // Для будущего бронирования
$carTitle = $input['car_title'] ?? null; // Для будущего бронирования
$carPrice = $input['car_price'] ?? null; // Для будущего бронирования

// Базовая валидация: номер телефона должен быть
if (!$phone) {
    http_response_code(400);
    echo json_encode(['error' => 'Не указан номер телефона']);
    exit;
}

// Формируем текст для звонка, используя подстановку переменных
// Это сообщение прочитает робот
$messageText = "Здравствуйте!";
if ($name) $messageText .= " Вас беспокоят из автосалона, $name.";
else $messageText .= " Вас беспокоят из автосалона.";
if ($carTitle) $messageText .= " Вы оставили заявку на автомобиль $carTitle.";
if ($carPrice) $messageText .= " Его стоимость составляет $carPrice рублей.";
$messageText .= " Наш менеджер свяжется с вами в ближайшее время.";

// Данные для запроса к Zvonok API
$postData = [
    'public_key' => $api_key,
    'phone' => $phone,
    'campaign_id' => $campaign_id,
    'text' => $messageText, // Передаём наш динамический текст
    'speaker' => 'oksana' // Например, голос Оксаны
];

// Инициализируем cURL-запрос
$ch = curl_init('https://zvonok.com/manager/cabapi_external/api/v1/phones/call/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Для локального тестирования. На боевом сервере лучше true

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Отвечаем фронтенду
if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Запрос на звонок отправлен. Ожидайте звонка.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Не удалось инициировать звонок. Попробуйте позже.']);
}
?>