<?php
// Параметры подключения (замените на свои)
$host = 'mysql-kroc400.alwaysdata.net'; // например, mysql-kroc400.alwaysdata.net
$dbname = 'kroc400_site_auto';                      // обычно совпадает с аккаунтом
$username = 'kroc400_db';              // обычно совпадает с аккаунтом
$password = 'R@2i/xEq';                   // пароль, который вы задали

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к базе данных успешно!";
} catch (PDOException $e) {
    die("❌ Ошибка подключения: " . $e->getMessage());
}
?>